<?php

namespace App\Models;

use Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $base_currency
 * @property string $quote_currency
 * @property string $rate
 * @property Carbon $rate_date
 * @property string $source
 */
class ExchangeRate extends Model
{
    /** @use HasFactory<ExchangeRateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'base_currency',
        'quote_currency',
        'rate',
        'rate_date',
        'source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'rate_date' => 'date',
        ];
    }
}
