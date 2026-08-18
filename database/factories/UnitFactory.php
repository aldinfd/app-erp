<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            // 3 huruf pertama saja bisa tabrakan antar kata berbeda
            // (author/autumn → "aut") → tambahkan angka agar selalu unik.
            'abbreviation' => substr($name, 0, 3).fake()->unique()->numberBetween(100, 999),
        ];
    }
}
