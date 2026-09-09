<?php

namespace App\Services;

use App\Contracts\ExchangeRateProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DatosUruguayExchangeRateProvider implements ExchangeRateProvider
{
    /**
     * @return array{rate: string, date: string, source: string}
     */
    public function latestUsdUyu(): array
    {
        $response = Http::connectTimeout(3)
            ->timeout(10)
            ->retry([200, 500])
            ->get(config('cuotas.exchange_rates.url'))
            ->throw();

        $latest = $response->json('data.0');

        if (! is_array($latest) || ! is_numeric($latest['value'] ?? null) || ! is_string($latest['date'] ?? null)) {
            throw new RuntimeException('La fuente de cotizaciones devolvió una respuesta inválida.');
        }

        return [
            'rate' => number_format((float) $latest['value'], 4, '.', ''),
            'date' => $latest['date'],
            'source' => 'datosUruguay/BCU',
        ];
    }
}
