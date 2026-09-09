<?php

namespace App\Services;

use App\Models\User;
use Revoltify\Subscriptionify\Enums\Interval;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;
use Revoltify\Subscriptionify\Models\Plan;
use Revoltify\Subscriptionify\Models\Subscription;

class AdminMetricsService
{
    public function signupsThisMonth(): int
    {
        return User::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function cancellationsThisMonth(): int
    {
        return Subscription::query()
            ->whereNotNull('cancelled_at')
            ->where('cancelled_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function monthlyRecurringRevenue(): float
    {
        return (float) Subscription::query()
            ->with('plan')
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
            ->get()
            ->sum(fn (Subscription $subscription): float => $this->subscriptionMrr($subscription));
    }

    public function subscriptionMrr(Subscription $subscription): float
    {
        /** @var Plan|null $plan */
        $plan = $subscription->plan;

        if ($plan === null || $plan->is_free) {
            return 0.0;
        }

        $family = match ($plan->slug) {
            'pro', 'pro-yearly' => 'pro',
            'familiar', 'familiar-yearly' => 'familiar',
            default => null,
        };

        if ($family === null) {
            return 0.0;
        }

        /** @var array{monthly: int, yearly: int} $prices */
        $prices = config("cuotas.prices.{$family}");

        if ($plan->billing_interval === Interval::Year) {
            return $prices['yearly'] / 12;
        }

        return (float) $prices['monthly'];
    }
}
