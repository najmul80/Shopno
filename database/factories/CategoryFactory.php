<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(rand(1, 3), true);
        return [
            // 'store_id' will be set by the seeder using for()
            'name' => Str::title($name),
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(3), // Ensure unique slug
            'description' => fake()->optional()->sentence(),
            'parent_id' => null, // Can be overridden by seeder for sub-categories
            'image_path' => null, // Or fake()->imageUrl(200, 200, 'cats', true, 'Faker')
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
