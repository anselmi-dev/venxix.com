<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'stripe',
            'provider_invoice_id' => fake()->unique()->uuid(),
            'description' => 'Plan Pro — mensual',
            'amount' => '199.00',
            'currency' => 'UYU',
            'status' => 'paid',
            'hosted_invoice_url' => null,
            'paid_at' => now(),
        ];
    }
}
