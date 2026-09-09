<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Models\Card;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'card_id' => Card::factory(),
            'merchant' => fake()->company(),
            'total_amount' => fake()->randomFloat(2, 500, 50000),
            'installment_count' => fake()->numberBetween(2, 12),
            'currency' => fake()->randomElement(Currency::cases()),
            'first_due_date' => fake()->dateTimeBetween('now', '+1 month'),
        ];
    }
}
