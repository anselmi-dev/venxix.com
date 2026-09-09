<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\StatementImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatementImport>
 */
class StatementImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'card_id' => Card::factory(),
            'original_name' => 'estado.csv',
            'path' => 'statement-imports/estado.csv',
            'status' => 'ready',
            'rows_count' => 1,
            'error' => null,
            'confirmed_at' => null,
        ];
    }
}
