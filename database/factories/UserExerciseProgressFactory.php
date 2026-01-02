<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserExerciseProgress>
 */
class UserExerciseProgressFactory extends Factory
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
            'exercise_id' => \App\Models\Exercise::factory(),
            'status' => 'current',
            'best_sets' => fake()->optional()->numberBetween(1, 5),
            'best_reps' => fake()->optional()->numberBetween(5, 20),
            'best_duration_seconds' => fake()->optional()->numberBetween(30, 300),
            'mastered_at' => null,
            'started_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
