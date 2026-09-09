<?php

use App\Models\ExchangeRate;
use App\Models\User;
use App\Services\ExchangeRateResolver;
use Illuminate\Support\Facades\Http;

test('refresh command stores the latest BCU exchange rate', function () {
    Http::fake([
        'datosuruguay.com/*' => Http::response([
            'data' => [
                [
                    'date' => '2026-09-04',
                    'series' => 'usd',
                    'value' => 40.243,
                ],
            ],
        ]),
    ]);

    $this->artisan('cuotas:refresh-exchange-rate')
        ->expectsOutput('USD/UYU 40.2430 (2026-09-04)')
        ->assertSuccessful();

    $this->assertDatabaseHas('exchange_rates', [
        'base_currency' => 'USD',
        'quote_currency' => 'UYU',
        'rate' => 40.2430,
        'rate_date' => '2026-09-04 00:00:00',
        'source' => 'datosUruguay/BCU',
    ]);
});

test('pro users receive the automatic rate while free users keep their manual rate', function () {
    ExchangeRate::factory()->create([
        'rate' => '40.2430',
        'rate_date' => '2026-09-04',
    ]);

    $pro = User::factory()->pro()->create(['usd_uyu_rate' => '39.0000']);
    $free = User::factory()->create(['usd_uyu_rate' => '41.0000']);
    $resolver = app(ExchangeRateResolver::class);

    expect($resolver->forUser($pro))
        ->rate->toBe('40.2430')
        ->automatic->toBeTrue()
        ->and($resolver->forUser($free))
        ->rate->toBe('41.0000')
        ->automatic->toBeFalse();
});
