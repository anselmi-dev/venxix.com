<?php

namespace App\Models;

use Database\Factories\StatementImportFactory;
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
 * @property int $card_id
 * @property string $original_name
 * @property string $path
 * @property string $status
 * @property int $rows_count
 * @property string|null $error
 * @property Carbon|null $confirmed_at
 */
class StatementImport extends Model
{
    /** @use HasFactory<StatementImportFactory> */
    use HasFactory, HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'card_id',
        'original_name',
        'path',
        'status',
        'rows_count',
        'error',
        'confirmed_at',
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * @return HasMany<StatementImportRow, $this>
     */
    public function rows(): HasMany
    {
        return $this->hasMany(StatementImportRow::class)->orderBy('row_number');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }
}
