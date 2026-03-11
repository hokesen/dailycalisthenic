<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exercise>
 */
class ExerciseFactory extends Factory
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
            'instructions' => fake()->paragraph(),
            'setup_text' => fake()->optional()->sentence(),
            'field_layout_notes' => fake()->optional()->sentence(),
            'difficulty_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced', 'expert']),
            'category' => fake()->randomElement(['push', 'pull', 'legs', 'core', 'full_body', 'cardio', 'flexibility']),
            'discipline' => 'general',
            'default_duration_seconds' => fake()->randomElement([30, 45, 60, 90, 120]),
            'media_url' => fake()->optional()->url(),
        ];
    }
}
