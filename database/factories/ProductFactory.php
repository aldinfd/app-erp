<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('SKU-####??'),
            'name' => fake()->words(2, true),
            'category_id' => null,
            'unit_id' => Unit::factory(),
            'cost_price' => fake()->randomFloat(2, 1, 100),
            'selling_price' => fake()->randomFloat(2, 100, 500),
            'reorder_point' => fake()->numberBetween(0, 20),
            'image_url' => null,
            'is_active' => true,
        ];
    }

    public function forCategory(?Category $category = null): static
    {
        return $this->state(fn () => [
            'category_id' => $category?->id ?? Category::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
