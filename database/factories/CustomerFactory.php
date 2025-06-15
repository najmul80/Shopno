<?php

namespace Database\Factories;

use App\Models\Customer; // Ensure Customer model is imported
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str; // Not strictly needed here unless using Str methods

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Decide if email and phone should be null for this instance
        $hasEmail = fake()->boolean(75); // 75% chance of having an email
        $hasPhone = fake()->boolean(85); // 85% chance of having a phone number

        return [
            // 'store_id' will be set by the seeder using for()
            'name' => fake()->name(),
            'email' => $hasEmail ? fake()->unique()->safeEmail() : null, // Generate email only if $hasEmail is true
            'phone_number' => $hasPhone ? fake()->unique()->phoneNumber() : null, // Generate phone only if $hasPhone is true
            'address_line1' => fake()->optional()->streetAddress(), // optional() works fine with direct generators
            'address_line2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state_province' => fake()->optional()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->country(),
            'photo_path' => null, // Or use fake()->imageUrl() if you set up image generation
            'is_active' => true,
            'notes' => fake()->optional(0.3)->paragraph(1), // 30% chance of having notes
        ];
    }
}