<?php

namespace App\Console\Commands;

use App\Contracts\ExchangeRateProvider;
use App\Models\ExchangeRate;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cuotas:refresh-exchange-rate')]
#[Description('Actualiza la cotización diaria USD/UYU')]
class RefreshExchangeRate extends Command
{
    public function handle(ExchangeRateProvider $provider): int
    {
        $latest = $provider->latestUsdUyu();

        ExchangeRate::query()->updateOrCreate(
            [
                'base_currency' => 'USD',
                'quote_currency' => 'UYU',
                'rate_date' => $latest['date'],
            ],
            [
                'rate' => $latest['rate'],
                'source' => $latest['source'],
            ],
        );

        $this->info('USD/UYU '.$latest['rate'].' ('.$latest['date'].')');

        return self::SUCCESS;
    }
}
