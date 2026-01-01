<?php

namespace Database\Factories;

use App\Models\Exercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExerciseProgression>
 */
class ExerciseProgressionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exercise_id' => Exercise::factory(),
            'easier_exercise_id' => null,
            'harder_exercise_id' => null,
            'order' => fake()->numberBetween(0, 10),
            'progression_path_name' => fake()->words(2, true),
        ];
    }
}
