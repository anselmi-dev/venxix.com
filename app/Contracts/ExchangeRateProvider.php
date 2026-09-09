<?php

namespace App\Contracts;

interface ExchangeRateProvider
{
    /**
     * @return array{rate: string, date: string, source: string}
     */
    public function latestUsdUyu(): array;
}
