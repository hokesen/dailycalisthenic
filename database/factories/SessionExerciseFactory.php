<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SessionExercise>
 */
class SessionExerciseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => \App\Models\Session::factory(),
            'exercise_id' => \App\Models\Exercise::factory(),
            'order' => 1,
            'duration_seconds' => fake()->numberBetween(30, 600),
            'notes' => fake()->optional()->sentence(),
            'difficulty_rating' => fake()->optional()->randomElement(['easy', 'medium', 'hard']),
            'started_at' => fake()->optional()->dateTime(),
            'completed_at' => fake()->optional()->dateTime(),
        ];
    }
}
