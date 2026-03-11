<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeditationLog>
 */
class MeditationLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'duration_seconds' => fake()->numberBetween(60, 1800),
            'technique' => 'breathing',
            'breath_cycles_completed' => fake()->numberBetween(1, 50),
            'notes' => fake()->optional()->sentence(),
            'practiced_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
