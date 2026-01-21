<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Session>
 */
class SessionFactory extends Factory
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
            'session_template_id' => null,
            'name' => fake()->words(3, true),
            'notes' => fake()->optional()->paragraph(),
            'started_at' => null,
            'completed_at' => null,
            'total_duration_seconds' => null,
            'status' => 'planned',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Enums\SessionStatus::Completed,
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Enums\SessionStatus::InProgress,
            'started_at' => now()->subMinutes(10),
        ]);
    }
}
