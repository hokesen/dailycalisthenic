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
}
