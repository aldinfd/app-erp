<?php

namespace Database\Factories;

use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChartOfAccount>
 */
class ChartOfAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('9-####'),
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(['asset', 'liability', 'equity', 'revenue', 'expense']),
            'parent_id' => null,
            'is_postable' => true,
            'is_active' => true,
        ];
    }
}
