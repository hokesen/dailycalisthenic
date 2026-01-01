<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SessionTemplate>
 */
class SessionTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'notes' => fake()->optional()->paragraph(),
            'estimated_duration_minutes' => fake()->numberBetween(15, 90),
            'default_rest_seconds' => fake()->randomElement([30, 45, 60, 90]),
        ];
    }
}
