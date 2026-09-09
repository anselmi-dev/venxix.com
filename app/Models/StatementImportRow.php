<?php

namespace App\Models;

use App\Enums\Currency;
use Database\Factories\StatementImportRowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $statement_import_id
 * @property int $row_number
 * @property string|null $merchant
 * @property string|null $total_amount
 * @property int|null $installment_count
 * @property Currency|null $currency
 * @property Carbon|null $first_due_date
 * @property array<string, list<string>>|null $validation_errors
 * @property bool $selected
 * @property int|null $purchase_id
 */
class StatementImportRow extends Model
{
    /** @use HasFactory<StatementImportRowFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'statement_import_id',
        'row_number',
        'merchant',
        'total_amount',
        'installment_count',
        'currency',
        'first_due_date',
        'validation_errors',
        'selected',
        'purchase_id',
    ];

    /**
     * @return BelongsTo<StatementImport, $this>
     */
    public function statementImport(): BelongsTo
    {
        return $this->belongsTo(StatementImport::class);
    }

    /**
     * @return BelongsTo<Purchase, $this>
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'currency' => Currency::class,
            'first_due_date' => 'date',
            'validation_errors' => 'array',
            'selected' => 'boolean',
        ];
    }
}
