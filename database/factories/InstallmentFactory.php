<?php

namespace Database\Factories;

use App\Enums\InstallmentStatus;
use App\Models\Installment;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Installment>
 */
class InstallmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'purchase_id' => Purchase::factory(),
            'number' => 1,
            'amount' => fake()->randomFloat(2, 100, 5000),
            'due_date' => fake()->dateTimeBetween('now', '+6 months'),
            'status' => InstallmentStatus::Pending,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => InstallmentStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
