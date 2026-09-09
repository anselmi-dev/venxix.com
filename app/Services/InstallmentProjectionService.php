<?php

namespace App\Services;

use App\Enums\Currency;
use App\Enums\InstallmentStatus;
use App\Models\Card;
use App\Models\Installment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InstallmentProjectionService
{
    public function __construct(private ExchangeRateResolver $exchangeRateResolver) {}

    /**
     * @return array{
     *     chart: list<array{label: string, amount: float, currency: string, segments: list<array{id: int, merchant: string, number: int, installment_count: int, amount: float}>}>,
     *     months: list<array{
     *         key: string,
     *         label: string,
     *         total: int,
     *         items: list<array{
     *             id: int,
     *             merchant: string,
     *             card_label: string|null,
     *             currency: string,
     *             amount: string,
     *             number: int,
     *             installment_count: int,
     *             status: string
     *         }>
     *     }>,
     *     stats: array{
     *         pending_uyu: string,
     *         pending_usd: string,
     *         next_free_month: string|null,
     *         active_installments: int
     *     }
     * }
     */
    public function forCard(Card $card, User $user): array
    {
        return $this->forUser($user, $card->id);
    }

    /**
     * @return array{
     *     chart: list<array{label: string, amount: float, currency: string, segments: list<array{id: int, merchant: string, number: int, installment_count: int, amount: float}>}>,
     *     months: list<array{
     *         key: string,
     *         label: string,
     *         total: int,
     *         items: list<array{
     *             id: int,
     *             merchant: string,
     *             card_label: string|null,
     *             currency: string,
     *             amount: string,
     *             number: int,
     *             installment_count: int,
     *             status: string
     *         }>
     *     }>,
     *     stats: array{
     *         pending_uyu: string,
     *         pending_usd: string,
     *         next_free_month: string|null,
     *         active_installments: int
     *     }
     * }
     */
    public function forUser(User $user, ?int $cardId = null): array
    {
        $query = Installment::query()
            ->where('user_id', $user->id)
            ->whereHas('purchase', function ($q) use ($cardId): void {
                if ($cardId !== null) {
                    $q->where('card_id', $cardId);
                }
            })
            ->with('purchase.card')
            ->where('status', InstallmentStatus::Pending)
            ->orderBy('due_date');

        $installments = $query->get();
        $includeCardLabel = $cardId === null;
        $exchangeRate = $this->exchangeRateResolver->forUser($user)['rate'];

        $pendingUyu = $installments
            ->filter(fn (Installment $i) => $i->purchase->currency === Currency::Uyu)
            ->sum(fn (Installment $i) => (float) $i->amount);

        $pendingUsd = $installments
            ->filter(fn (Installment $i) => $i->purchase->currency === Currency::Usd)
            ->sum(fn (Installment $i) => (float) $i->amount);

        $grouped = $installments->groupBy(fn (Installment $i) => $i->due_date->format('Y-m'));

        $chart = $grouped
            ->sortKeys()
            ->take(6)
            ->map(function (Collection $items, string $key) use ($user, $exchangeRate) {
                $date = Carbon::createFromFormat('Y-m', $key)->startOfMonth();
                $amount = $items->sum(fn (Installment $i) => $this->convertAmount($i, $user, $exchangeRate));

                $segments = $items
                    ->map(fn (Installment $i) => [
                        'id' => $i->id,
                        'merchant' => $i->purchase->merchant,
                        'number' => $i->number,
                        'installment_count' => $i->purchase->installment_count,
                        'amount' => round($this->convertAmount($i, $user, $exchangeRate), 2),
                    ])
                    ->sortByDesc('amount')
                    ->values()
                    ->all();

                return [
                    'label' => $this->monthLabel($date),
                    'amount' => round($amount, 2),
                    'currency' => $user->preferred_currency->value,
                    'segments' => $segments,
                ];
            })
            ->values()
            ->all();

        $months = $grouped
            ->sortKeys()
            ->map(function (Collection $items, string $key) use ($includeCardLabel) {
                $date = Carbon::createFromFormat('Y-m', $key)->startOfMonth();

                return [
                    'key' => $key,
                    'label' => $this->monthLabel($date).' '.$date->year,
                    'total' => $items->count(),
                    'items' => array_values($items->map(fn (Installment $i) => [
                        'id' => $i->id,
                        'merchant' => $i->purchase->merchant,
                        'card_label' => $includeCardLabel ? $i->purchase->card->displayName() : null,
                        'currency' => $i->purchase->currency->value,
                        'amount' => $i->amount,
                        'number' => $i->number,
                        'installment_count' => $i->purchase->installment_count,
                        'status' => $i->status->value,
                    ])->all()),
                ];
            })
            ->values()
            ->all();

        $lastPending = $installments->sortByDesc('due_date')->first();
        $nextFreeMonth = $lastPending
            ? $this->monthLabel($lastPending->due_date->copy()->addMonth()).' '.$lastPending->due_date->copy()->addMonth()->year
            : null;

        return [
            'chart' => array_values($chart),
            'months' => array_values($months),
            'stats' => [
                'pending_uyu' => number_format($pendingUyu, 2, '.', ''),
                'pending_usd' => number_format($pendingUsd, 2, '.', ''),
                'next_free_month' => $nextFreeMonth,
                'active_installments' => $installments->count(),
            ],
        ];
    }

    private function convertAmount(Installment $installment, User $user, ?string $exchangeRate): float
    {
        $amount = (float) $installment->amount;
        $currency = $installment->purchase->currency;

        if ($currency === $user->preferred_currency) {
            return $amount;
        }

        if ($exchangeRate === null) {
            return $amount;
        }

        $rate = (float) $exchangeRate;

        if ($currency === Currency::Usd && $user->preferred_currency === Currency::Uyu) {
            return $amount * $rate;
        }

        if ($currency === Currency::Uyu && $user->preferred_currency === Currency::Usd) {
            return $rate > 0 ? $amount / $rate : $amount;
        }

        return $amount;
    }

    private function monthLabel(Carbon|CarbonInterface $date): string
    {
        $parsed = Carbon::parse($date);
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $months[$parsed->month];
    }
}
