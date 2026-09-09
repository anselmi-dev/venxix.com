<?php

namespace App\Jobs;

use App\Enums\Currency;
use App\Models\StatementImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ParseStatementImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $statementImportId) {}

    public function handle(): void
    {
        $import = StatementImport::query()->with('user')->findOrFail($this->statementImportId);

        if (filled($import->user?->locale)) {
            app()->setLocale($import->user->locale);
        }

        $import->update(['status' => 'processing', 'error' => null]);
        $import->rows()->delete();

        $stream = Storage::disk('local')->readStream($import->path);

        if (! is_resource($stream)) {
            throw new RuntimeException('No se pudo abrir el archivo importado.');
        }

        try {
            $firstLine = fgets($stream);

            if ($firstLine === false) {
                throw new RuntimeException(__('ui.imports.empty_csv'));
            }

            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            rewind($stream);

            $headers = fgetcsv($stream, separator: $delimiter);

            if ($headers === false) {
                throw new RuntimeException('No se pudo leer el encabezado del CSV.');
            }

            $columns = $this->resolveColumns($headers);
            $rowNumber = 1;
            $rowsCount = 0;

            while (($values = fgetcsv($stream, separator: $delimiter)) !== false) {
                $rowNumber++;

                if ($this->isBlankRow($values)) {
                    continue;
                }

                $data = [
                    'merchant' => $this->value($values, $columns['merchant']),
                    'total_amount' => $this->normalizeAmount($this->value($values, $columns['total_amount'])),
                    'installment_count' => $this->value($values, $columns['installment_count']),
                    'currency' => strtoupper($this->value($values, $columns['currency'])),
                    'first_due_date' => $this->normalizeDate($this->value($values, $columns['first_due_date'])),
                ];

                $validator = Validator::make($data, [
                    'merchant' => ['required', 'string', 'max:255'],
                    'total_amount' => ['required', 'numeric', 'min:0.01'],
                    'installment_count' => ['required', 'integer', 'min:1', 'max:48'],
                    'currency' => ['required', 'in:'.implode(',', Currency::values())],
                    'first_due_date' => ['required', 'date_format:Y-m-d'],
                ]);

                $errors = $validator->fails() ? $validator->errors()->toArray() : null;

                if ($errors === null && $this->isDuplicatePurchase($import, $data)) {
                    $errors = [
                        'merchant' => [__('ui.imports.duplicate')],
                    ];
                }

                $import->rows()->create([
                    'row_number' => $rowNumber,
                    ...$data,
                    'validation_errors' => $errors,
                    'selected' => $errors === null,
                ]);
                $rowsCount++;
            }

            if ($rowsCount === 0) {
                throw new RuntimeException('El archivo no contiene compras para importar.');
            }

            $import->update([
                'status' => 'ready',
                'rows_count' => $rowsCount,
            ]);
        } finally {
            fclose($stream);
        }
    }

    public function failed(?Throwable $exception): void
    {
        StatementImport::query()
            ->whereKey($this->statementImportId)
            ->update([
                'status' => 'failed',
                'error' => $exception?->getMessage() ?? 'No se pudo procesar el archivo.',
            ]);
    }

    /**
     * @param  list<string|null>  $headers
     * @return array{merchant: int, total_amount: int, installment_count: int, currency: int, first_due_date: int}
     */
    private function resolveColumns(array $headers): array
    {
        $aliases = [
            'merchant' => ['merchant', 'comercio', 'descripcion', 'descripcion_comercio', 'establecimiento'],
            'total_amount' => ['total_amount', 'monto_total', 'monto', 'importe', 'importe_total', 'total'],
            'installment_count' => ['installment_count', 'cantidad_cuotas', 'cuotas', 'n_cuotas', 'numero_cuotas'],
            'currency' => ['currency', 'moneda', 'divisa'],
            'first_due_date' => ['first_due_date', 'primer_vencimiento', 'fecha_vencimiento', 'vencimiento', 'fecha_primer_vencimiento'],
        ];

        $normalized = collect($headers)
            ->map(fn (?string $header) => Str::of((string) $header)->trim()->replace("\u{FEFF}", '')->ascii()->snake()->toString())
            ->all();

        $columns = [];

        foreach ($aliases as $field => $names) {
            $index = collect($normalized)->search(fn (string $header) => in_array($header, $names, true));

            if ($index === false) {
                throw new RuntimeException('Falta la columna obligatoria: '.$names[0].'.');
            }

            $columns[$field] = $index;
        }

        /** @var array{merchant: int, total_amount: int, installment_count: int, currency: int, first_due_date: int} $columns */
        return $columns;
    }

    /**
     * @param  list<string|null>  $values
     */
    private function isBlankRow(array $values): bool
    {
        return collect($values)->every(fn (?string $value) => trim((string) $value) === '');
    }

    /**
     * @param  list<string|null>  $values
     */
    private function value(array $values, int $index): string
    {
        return trim((string) ($values[$index] ?? ''));
    }

    private function normalizeAmount(string $value): ?string
    {
        $normalized = str_replace(['$', 'U$', ' ', "\u{00A0}"], '', $value);

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
        }

        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized)
            ? number_format((float) $normalized, 2, '.', '')
            : null;
    }

    private function normalizeDate(string $value): ?string
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);

            if ($date !== false && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * @param  array{merchant: string, total_amount: string|null, installment_count: string, currency: string, first_due_date: string|null}  $data
     */
    private function isDuplicatePurchase(StatementImport $import, array $data): bool
    {
        if ($data['total_amount'] === null || $data['first_due_date'] === null) {
            return false;
        }

        return $import->user->purchases()
            ->where('card_id', $import->card_id)
            ->where('merchant', $data['merchant'])
            ->where('total_amount', $data['total_amount'])
            ->where('installment_count', (int) $data['installment_count'])
            ->where('currency', $data['currency'])
            ->whereDate('first_due_date', $data['first_due_date'])
            ->exists();
    }
}
