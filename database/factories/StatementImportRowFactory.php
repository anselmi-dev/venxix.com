<?php

namespace Database\Factories;

use App\Models\StatementImport;
use App\Models\StatementImportRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatementImportRow>
 */
class StatementImportRowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'statement_import_id' => StatementImport::factory(),
            'row_number' => 2,
            'merchant' => fake()->company(),
            'total_amount' => fake()->randomFloat(2, 10, 5000),
            'installment_count' => fake()->numberBetween(1, 24),
            'currency' => fake()->randomElement(['UYU', 'USD']),
            'first_due_date' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'validation_errors' => null,
            'selected' => true,
            'purchase_id' => null,
        ];
    }
}
