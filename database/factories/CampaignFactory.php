<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'title' => fake()->unique()->sentence(3),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'goal_amount' => fake()->randomFloat(2, 100, 100000),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'category' => fake()->randomElement(['education', 'health', 'food', 'environment', 'community', 'other']),
            'status' => fake()->randomElement(['active', 'upcoming', 'completed']),
            'image_path' => null,
        ];
    }
}
