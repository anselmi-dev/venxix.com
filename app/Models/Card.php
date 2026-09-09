<?php

namespace App\Models;

use App\Enums\CardType;
use App\Enums\Currency;
use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property string $bank
 * @property string|null $alias
 * @property string $last_four
 * @property CardType $type
 * @property int|null $closing_day
 * @property string|null $credit_limit
 * @property Currency|null $credit_limit_currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory, HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'bank',
        'alias',
        'last_four',
        'type',
        'closing_day',
        'credit_limit',
        'credit_limit_currency',
    ];

    /**
     * The column that receives the model's ULID, kept separate from the
     * auto-incrementing primary key so foreign keys stay integers.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function displayName(): string
    {
        if ($this->alias) {
            return $this->alias;
        }

        return 'Tarjeta ····'.$this->last_four;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Purchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * @return HasMany<Note, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CardType::class,
            'closing_day' => 'integer',
            'credit_limit' => 'decimal:2',
            'credit_limit_currency' => Currency::class,
        ];
    }
}
