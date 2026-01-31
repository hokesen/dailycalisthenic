<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JournalExercise>
 */
class JournalExerciseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journal_entry_id' => \App\Models\JournalEntry::factory(),
            'name' => fake()->words(2, true),
            'duration_minutes' => fake()->optional()->numberBetween(5, 60),
            'notes' => fake()->optional()->sentence(),
            'order' => 1,
        ];
    }
}
