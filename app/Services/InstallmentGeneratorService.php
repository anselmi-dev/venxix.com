<?php

namespace App\Services;

use App\Enums\InstallmentStatus;
use App\Models\Purchase;
use Illuminate\Support\Carbon;

class InstallmentGeneratorService
{
    /**
     * @return list<array{number: int, amount: string, due_date: Carbon, status: InstallmentStatus}>
     */
    public function generateInstallments(Purchase $purchase): array
    {
        $count = $purchase->installment_count;
        $totalCents = (int) round((float) $purchase->total_amount * 100);
        $baseCents = intdiv($totalCents, $count);
        $remainder = $totalCents - ($baseCents * $count);

        $installments = [];

        for ($i = 1; $i <= $count; $i++) {
            $cents = $baseCents + ($i === $count ? $remainder : 0);
            $installments[] = [
                'number' => $i,
                'amount' => number_format($cents / 100, 2, '.', ''),
                'due_date' => $purchase->first_due_date->copy()->addMonths($i - 1),
                'status' => InstallmentStatus::Pending,
            ];
        }

        return $installments;
    }

    public function createForPurchase(Purchase $purchase): void
    {
        foreach ($this->generateInstallments($purchase) as $data) {
            $purchase->installments()->create([
                'user_id' => $purchase->user_id,
                'number' => $data['number'],
                'amount' => $data['amount'],
                'due_date' => $data['due_date'],
                'status' => $data['status'],
            ]);
        }
    }
}
