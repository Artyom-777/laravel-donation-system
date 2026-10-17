<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'donor_id' => Donor::factory(),
            'amount' => fake()->randomFloat(2, 1, 5000),
            'payment_method' => fake()->randomElement(['Credit Card', 'PayPal', 'Bank Transfer']),
            'status' => fake()->randomElement(['completed', 'pending', 'failed']),
            'payment_reference' => fake()->optional()->uuid(),
            'donated_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
