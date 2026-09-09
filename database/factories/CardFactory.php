<?php

namespace Database\Factories;

use App\Enums\CardType;
use App\Enums\Currency;
use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'bank' => fake()->randomElement(['Santander', 'Itaú', 'BROU', 'BBVA']),
            'alias' => fake()->optional()->words(2, true),
            'last_four' => fake()->numerify('####'),
            'type' => fake()->randomElement(CardType::cases()),
            'closing_day' => null,
            'credit_limit' => null,
            'credit_limit_currency' => null,
        ];
    }

    /**
     * @return Factory<Card>
     */
    public function withStatement(int $closingDay = 15, float $creditLimit = 50000, Currency $currency = Currency::Uyu): Factory
    {
        return $this->state(fn (array $attributes) => [
            'closing_day' => $closingDay,
            'credit_limit' => $creditLimit,
            'credit_limit_currency' => $currency,
        ]);
    }
}
