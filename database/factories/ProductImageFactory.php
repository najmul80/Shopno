<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 'product_id' will be set by the seeder using has()
            'image_path' => 'product-images-dummy/' . fake()->image('public/storage/product-images-dummy', 400, 300, 'technics', false),
            // 'image_path' => 'https://picsum.photos/400/300?random=' . rand(), // Using picsum for online images
            'caption' => fake()->optional()->sentence(3),
            'is_primary' => false, // Seeder can override this for one image
            'sort_order' => fake()->numberBetween(0, 5),
        ];
    }
   
}
