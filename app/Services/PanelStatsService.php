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

class PanelStatsService
{
    /**
     * @return array{
     *     pending_uyu: string,
     *     pending_usd: string,
     *     card_count: int,
     *     next_due: array{date: string, merchant: string, amount: string, currency: string}|null
     * }
     */
    public function stats(User $user): array
    {
        $pending = Installment::query()
            ->where('user_id', $user->id)
            ->where('status', InstallmentStatus::Pending)
            ->with('purchase')
            ->get();

        $pendingUyu = $pending
            ->filter(fn (Installment $i) => $i->purchase->currency === Currency::Uyu)
            ->sum(fn (Installment $i) => (float) $i->amount);

        $pendingUsd = $pending
            ->filter(fn (Installment $i) => $i->purchase->currency === Currency::Usd)
            ->sum(fn (Installment $i) => (float) $i->amount);

        $next = $pending->sortBy('due_date')->first();

        return [
            'pending_uyu' => number_format($pendingUyu, 2, '.', ''),
            'pending_usd' => number_format($pendingUsd, 2, '.', ''),
            'card_count' => $user->cards()->count(),
            'next_due' => $next ? [
                'date' => $next->due_date->format('d M'),
                'merchant' => $next->purchase->merchant,
                'amount' => $next->amount,
                'currency' => $next->purchase->currency->value,
            ] : null,
        ];
    }

    /**
     * @return list<array{
     *     id: int,
     *     display_name: string,
     *     bank: string,
     *     last_four: string,
     *     type: string,
     *     type_label: string,
     *     pending_uyu: string,
     *     pending_usd: string,
     *     active_installments: int,
     *     sparkline: list<array{height: float, label: string, amount_uyu: float, amount_usd: float, count: int}>,
     *     period_label: string|null,
     *     closing_day: int|null,
     *     credit_limit: string|null,
     *     credit_limit_currency: string|null
     * }>
     */
    public function cardsForPanel(User $user): array
    {
        return array_values($user->cards()
            ->orderBy('created_at')
            ->get()
            ->map(function (Card $card) use ($user) {
                $pending = Installment::query()
                    ->where('user_id', $user->id)
                    ->where('status', InstallmentStatus::Pending)
                    ->whereHas('purchase', fn ($q) => $q->where('card_id', $card->id))
                    ->with('purchase')
                    ->orderBy('due_date')
                    ->get();

                $pendingUyu = $pending
                    ->filter(fn (Installment $i) => $i->purchase->currency === Currency::Uyu)
                    ->sum(fn (Installment $i) => (float) $i->amount);

                $pendingUsd = $pending
                    ->filter(fn (Installment $i) => $i->purchase->currency === Currency::Usd)
                    ->sum(fn (Installment $i) => (float) $i->amount);

                $sparkline = $this->sparklineForCard($pending);
                $first = $pending->first();
                $last = $pending->last();

                $periodLabel = ($first && $last)
                    ? $this->shortMonth($first->due_date).' → '.$this->shortMonth($last->due_date).' '.$last->due_date->year
                    : null;

                return [
                    'id' => $card->id,
                    'ulid' => $card->ulid,
                    'display_name' => $card->displayName(),
                    'bank' => $card->bank,
                    'last_four' => $card->last_four,
                    'type' => $card->type->value,
                    'type_label' => $card->type->label(),
                    'pending_uyu' => number_format($pendingUyu, 2, '.', ''),
                    'pending_usd' => number_format($pendingUsd, 2, '.', ''),
                    'active_installments' => $pending->count(),
                    'sparkline' => $sparkline,
                    'period_label' => $periodLabel,
                    'closing_day' => $card->closing_day,
                    'credit_limit' => $card->credit_limit,
                    'credit_limit_currency' => $card->credit_limit_currency?->value,
                ];
            })
            ->all());
    }

    /**
     * @return list<array{
     *     id: int,
     *     day: int,
     *     type: string,
     *     title: string,
     *     meta: string,
     *     amount: string|null,
     *     currency: string|null,
     *     status: string,
     *     status_label: string,
     *     card_last_four: string
     * }>
     */
    public function calendarEvents(User $user, int $year, int $month, ?string $statusFilter = null, ?int $selectedDay = null): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $query = Installment::query()
            ->where('user_id', $user->id)
            ->whereBetween('due_date', [$start, $end])
            ->with(['purchase.card'])
            ->orderBy('due_date');

        if ($statusFilter === 'pending') {
            $query->where('status', InstallmentStatus::Pending);
        } elseif ($statusFilter === 'paid') {
            $query->where('status', InstallmentStatus::Paid);
        }

        if ($selectedDay !== null) {
            $query->whereDay('due_date', $selectedDay);
        }

        $dueEvents = $query->get()->map(function (Installment $installment) {
            $purchase = $installment->purchase;
            $card = $purchase->card;

            $statusKey = match (true) {
                $installment->status === InstallmentStatus::Paid => 'paid',
                $installment->due_date->isPast() => 'due_soon',
                $installment->due_date->diffInDays(now()) <= 3 => 'due_soon',
                default => 'pending',
            };

            $status = match ($statusKey) {
                'paid' => 'pagada',
                'due_soon' => 'pronto',
                default => 'pendiente',
            };

            return [
                'id' => $installment->id,
                'day' => $installment->due_date->day,
                'type' => 'due',
                'title' => $purchase->merchant,
                'meta' => __('ui.panel.installment_meta', [
                    'number' => $installment->number,
                    'total' => $purchase->installment_count,
                    'last_four' => $card->last_four,
                ]),
                'amount' => $installment->amount,
                'currency' => $purchase->currency->value,
                'status' => $status,
                'status_label' => __('ui.status.'.$statusKey),
                'card_last_four' => $card->last_four,
            ];
        });

        $closingEvents = $this->closingEventsForMonth($user, $end, $selectedDay);

        return array_values($dueEvents->concat($closingEvents)->sortBy('day')->values()->all());
    }

    /**
     * @return list<int>
     */
    public function calendarMarkedDays(User $user, int $year, int $month, ?string $statusFilter = null): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $query = Installment::query()
            ->where('user_id', $user->id)
            ->whereBetween('due_date', [$start, $end]);

        if ($statusFilter === 'pending') {
            $query->where('status', InstallmentStatus::Pending);
        } elseif ($statusFilter === 'paid') {
            $query->where('status', InstallmentStatus::Paid);
        }

        return array_values($query
            ->pluck('due_date')
            ->map(fn ($date) => Carbon::parse($date)->day)
            ->unique()
            ->values()
            ->all());
    }

    /**
     * @return list<int>
     */
    public function calendarClosingDays(User $user, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return array_values($this->closingEventsForMonth($user, $end)
            ->pluck('day')
            ->unique()
            ->values()
            ->all());
    }

    /**
     * @return Collection<int, array{
     *     id: int,
     *     day: int,
     *     type: string,
     *     title: string,
     *     meta: string,
     *     amount: string|null,
     *     currency: string|null,
     *     status: string,
     *     status_label: string,
     *     card_last_four: string
     * }>
     */
    private function closingEventsForMonth(User $user, Carbon $end, ?int $selectedDay = null): Collection
    {
        return $user->cards()
            ->whereNotNull('closing_day')
            ->get()
            ->map(function (Card $card) use ($end) {
                $day = min((int) $card->closing_day, $end->day);

                return [
                    'id' => -$card->id,
                    'day' => $day,
                    'type' => 'closing',
                    'title' => $card->displayName(),
                    'meta' => __('ui.panel.closing_meta', ['last_four' => $card->last_four]),
                    'amount' => $card->credit_limit,
                    'currency' => $card->credit_limit_currency?->value,
                    'status' => 'closing',
                    'status_label' => __('ui.status.closing'),
                    'card_last_four' => $card->last_four,
                ];
            })
            ->when($selectedDay !== null, fn (Collection $events) => $events->filter(fn (array $event) => $event['day'] === $selectedDay))
            ->values();
    }

    /**
     * @param  Collection<int, Installment>  $pending
     * @return list<array{height: float, label: string, amount_uyu: float, amount_usd: float, count: int}>
     */
    private function sparklineForCard(Collection $pending): array
    {
        $now = now()->startOfMonth();
        $months = [];

        for ($i = 0; $i < 6; $i++) {
            $monthStart = $now->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $monthInstallments = $pending
                ->filter(fn (Installment $inst) => $inst->due_date->between($monthStart, $monthEnd));

            $amountUyu = $monthInstallments
                ->filter(fn (Installment $inst) => $inst->purchase->currency === Currency::Uyu)
                ->sum(fn (Installment $inst) => (float) $inst->amount);

            $amountUsd = $monthInstallments
                ->filter(fn (Installment $inst) => $inst->purchase->currency === Currency::Usd)
                ->sum(fn (Installment $inst) => (float) $inst->amount);

            $months[] = [
                'sum' => round($amountUyu + $amountUsd, 2),
                'label' => $this->shortMonth($monthStart).' '.$monthStart->year,
                'amount_uyu' => round($amountUyu, 2),
                'amount_usd' => round($amountUsd, 2),
                'count' => $monthInstallments->count(),
            ];
        }

        $max = collect($months)->max('sum') ?: 1;

        return array_map(fn (array $month) => [
            'height' => round(($month['sum'] / $max) * 100),
            'label' => $month['label'],
            'amount_uyu' => $month['amount_uyu'],
            'amount_usd' => $month['amount_usd'],
            'count' => $month['count'],
        ], $months);
    }

    private function shortMonth(Carbon|CarbonInterface $date): string
    {
        $months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        return $months[Carbon::parse($date)->month - 1];
    }
}
