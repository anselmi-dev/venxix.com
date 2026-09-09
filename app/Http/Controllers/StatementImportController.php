<?php

namespace App\Http\Controllers;

use App\Billing\PlanAccess;
use App\Http\Requests\ConfirmStatementImportRequest;
use App\Http\Requests\StoreStatementImportRequest;
use App\Jobs\ParseStatementImport;
use App\Models\StatementImport;
use App\Services\InstallmentGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatementImportController extends Controller
{
    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            echo "\xEF\xBB\xBF";
            echo "comercio,monto_total,cantidad_cuotas,moneda,primer_vencimiento\n";
            echo "Tienda ejemplo,1290.00,6,UYU,2026-10-15\n";
        }, 'plantilla-cuotas.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create(Request $request, PlanAccess $planAccess): Response
    {
        abort_unless($planAccess->canImportStatements($request->user()), 403);

        $cardId = $request->integer('card') ?: null;
        $userCards = $request->user()->cards()->orderBy('bank')->get();

        if ($cardId !== null && ! $userCards->contains('id', $cardId)) {
            abort(404);
        }

        return Inertia::render('Imports/Create', [
            'preselectedCardId' => $cardId,
            'cards' => $userCards
                ->map(fn ($card) => [
                    'id' => $card->id,
                    'bank' => $card->bank,
                    'last_four' => $card->last_four,
                    'alias' => $card->alias,
                ]),
            'recentImports' => $request->user()
                ->statementImports()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (StatementImport $import) => [
                    'ulid' => $import->ulid,
                    'name' => $import->original_name,
                    'status' => $import->status,
                    'rows_count' => $import->rows_count,
                    'created_at' => $import->created_at?->diffForHumans(),
                ]),
        ]);
    }

    public function store(StoreStatementImportRequest $request): RedirectResponse
    {
        $file = $request->file('statement');
        $path = $file->store('statement-imports', 'local');

        if ($path === false) {
            throw ValidationException::withMessages([
                'statement' => 'No pudimos guardar el archivo para procesarlo.',
            ]);
        }

        $import = $request->user()->statementImports()->create([
            'card_id' => $request->integer('card_id'),
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'status' => 'pending',
        ]);

        ParseStatementImport::dispatch($import->id);

        return redirect()->route('imports.show', $import);
    }

    public function show(Request $request, StatementImport $statementImport): Response
    {
        abort_unless($request->user()->id === $statementImport->user_id, 404);

        $statementImport->load(['card', 'rows']);

        return Inertia::render('Imports/Show', [
            'statementImport' => [
                'ulid' => $statementImport->ulid,
                'name' => $statementImport->original_name,
                'status' => $statementImport->status,
                'error' => $statementImport->error,
                'rows_count' => $statementImport->rows_count,
                'card' => $statementImport->card->displayName().' · ····'.$statementImport->card->last_four,
                'rows' => $statementImport->rows->map(fn ($row) => [
                    'id' => $row->id,
                    'row_number' => $row->row_number,
                    'merchant' => $row->merchant,
                    'total_amount' => $row->total_amount,
                    'installment_count' => $row->installment_count,
                    'currency' => $row->currency?->value,
                    'first_due_date' => $row->first_due_date?->toDateString(),
                    'validation_errors' => $row->validation_errors,
                    'selected' => $row->selected,
                    'imported' => $row->purchase_id !== null,
                ]),
            ],
        ]);
    }

    public function confirm(
        ConfirmStatementImportRequest $request,
        StatementImport $statementImport,
        InstallmentGeneratorService $installmentGenerator,
    ): RedirectResponse {
        $rowIds = collect($request->rowIds());

        DB::transaction(function () use ($statementImport, $rowIds, $installmentGenerator): void {
            $lockedImport = $statementImport->newQuery()
                ->whereKey($statementImport->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedImport->status !== 'ready') {
                throw ValidationException::withMessages([
                    'row_ids' => __('ui.imports.not_ready'),
                ]);
            }

            $rows = $lockedImport->rows()
                ->whereIn('id', $rowIds)
                ->whereNull('validation_errors')
                ->whereNull('purchase_id')
                ->lockForUpdate()
                ->get();

            if ($rows->count() !== $rowIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'row_ids' => __('ui.imports.invalid_rows'),
                ]);
            }

            foreach ($rows as $row) {
                $purchase = $lockedImport->user->purchases()->create([
                    'card_id' => $lockedImport->card_id,
                    'merchant' => $row->merchant,
                    'total_amount' => $row->total_amount,
                    'installment_count' => $row->installment_count,
                    'currency' => $row->currency,
                    'first_due_date' => $row->first_due_date,
                ]);

                $installmentGenerator->createForPurchase($purchase);
                $row->update(['purchase_id' => $purchase->id]);
            }

            $lockedImport->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);
        });

        Storage::disk('local')->delete($statementImport->path);

        return redirect()
            ->route('panel')
            ->with('success', $rowIds->count().' compras importadas.');
    }
}
