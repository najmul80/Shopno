<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Invoice number, sub_total, grand_total etc. will be set/overridden by the seeder for accuracy
        return [
            // 'store_id', 'user_id', 'customer_id' will be set by the seeder
            'invoice_number' => 'TEMP-' . strtoupper(Str::random(10)), // Temporary, seeder will generate proper one
            'sub_total' => 0, // Will be calculated by seeder
            'discount_amount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'shipping_charge' => 0,
            'grand_total' => 0, // Will be calculated by seeder
            'amount_paid' => 0,
            'change_returned' => 0,
            'payment_method' => fake()->randomElement(['cash', 'card', 'mobile_banking']),
            'payment_status' => 'pending', // Seeder might update to 'paid'
            'sale_status' => fake()->randomElement(['completed', 'pending', 'processing']),
            'notes' => fake()->optional()->sentence(),
            'created_at' => fake()->dateTimeThisYear(), // Random date/time within this year
        ];
    }
}
