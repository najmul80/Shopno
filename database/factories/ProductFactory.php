<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(rand(2, 4), true);
        return [
            // 'store_id' and 'category_id' will be set by the seeder
            'name' => Str::title($name),
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(5),
            'description' => fake()->paragraph(3),
            'sku' => 'SKU-' . strtoupper(Str::random(3)) . fake()->unique()->numberBetween(100, 999),
            'purchase_price' => fake()->optional(0.7, 0.00)->randomFloat(2, 10, 500), // 70% chance of having purchase price
            'sale_price' => fake()->randomFloat(2, 50, 1000),
            'stock_quantity' => fake()->numberBetween(0, 200), // Some products can be out of stock
            'low_stock_threshold' => fake()->numberBetween(5, 20),
            'unit' => fake()->randomElement(['pcs', 'kg', 'ltr', 'box', 'set']),
            'is_active' => true,
            'is_featured' => fake()->boolean(20), // 20% chance of being featured
            'attributes' => null, // Or json_encode(['color' => fake()->colorName(), 'size' => fake()->randomElement(['S','M','L'])])
        ];
    }
}
