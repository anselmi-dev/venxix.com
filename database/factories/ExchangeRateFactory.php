<?php

namespace Database\Factories;

use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'base_currency' => 'USD',
            'quote_currency' => 'UYU',
            'rate' => fake()->randomFloat(4, 35, 50),
            'rate_date' => now()->toDateString(),
            'source' => 'datosUruguay/BCU',
        ];
    }
}
