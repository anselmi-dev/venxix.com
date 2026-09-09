<?php

namespace App\Services;

use App\Billing\PlanAccess;
use App\Models\ExchangeRate;
use App\Models\User;

class ExchangeRateResolver
{
    public function __construct(private PlanAccess $planAccess) {}

    /**
     * @return array{rate: string|null, automatic: bool, date: string|null, source: string|null}
     */
    public function forUser(User $user): array
    {
        if ($this->planAccess->hasAutomaticExchangeRate($user)) {
            $latest = ExchangeRate::query()
                ->where('base_currency', 'USD')
                ->where('quote_currency', 'UYU')
                ->latest('rate_date')
                ->first();

            if ($latest !== null) {
                return [
                    'rate' => $latest->rate,
                    'automatic' => true,
                    'date' => $latest->rate_date->toDateString(),
                    'source' => $latest->source,
                ];
            }
        }

        return [
            'rate' => $user->usd_uyu_rate,
            'automatic' => false,
            'date' => null,
            'source' => null,
        ];
    }
}
