<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Store',
            'description' => fake()->paragraph(),
            'address_line1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state_province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->country(),
            'phone_number' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'website' => 'https://' . fake()->domainName(),
            'logo_path' => null, // You can add logic to copy a dummy logo here
            'is_active' => true,
        ];
    }
}
