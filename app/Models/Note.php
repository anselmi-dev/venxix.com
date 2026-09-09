<?php

namespace App\Models;

use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $card_id
 * @property string $body
 * @property bool $is_flagged
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    public const int BODY_MAX_LENGTH = 280;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'card_id',
        'body',
        'is_flagged',
    ];

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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_flagged' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
