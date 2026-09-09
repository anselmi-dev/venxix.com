<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use Database\Factories\InstallmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $purchase_id
 * @property int $number
 * @property string $amount
 * @property Carbon $due_date
 * @property InstallmentStatus $status
 * @property Carbon|null $paid_at
 * @property Carbon|null $reminder_sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Installment extends Model
{
    /** @use HasFactory<InstallmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'purchase_id',
        'number',
        'amount',
        'due_date',
        'status',
        'paid_at',
        'reminder_sent_at',
    ];

    public function isPending(): bool
    {
        return $this->status === InstallmentStatus::Pending;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'status' => InstallmentStatus::class,
            'paid_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }
}
