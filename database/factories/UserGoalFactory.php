<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserGoal>
 */
class UserGoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sessions_per_week' => $this->faker->numberBetween(1, 7),
            'minimum_session_duration_minutes' => $this->faker->numberBetween(5, 60),
            'exercise_goals' => null,
            'is_active' => true,
            'starts_at' => now(),
            'ends_at' => null,
        ];
    }
}
