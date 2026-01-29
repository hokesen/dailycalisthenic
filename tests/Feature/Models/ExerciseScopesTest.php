<?php

namespace Tests\Feature\Models;

use App\Models\Exercise;
use App\Models\ExerciseProgression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_with_progression_eager_loads_progression_relationships(): void
    {
        $exercise1 = Exercise::factory()->create(['name' => 'Kneeling Push-ups']);
        $exercise2 = Exercise::factory()->create(['name' => 'Regular Push-ups']);
        $exercise3 = Exercise::factory()->create(['name' => 'Pike Push-ups']);

        ExerciseProgression::factory()->create([
            'exercise_id' => $exercise1->id,
            'progression_path_name' => 'push-ups',
            'order' => 1,
            'harder_exercise_id' => $exercise2->id,
        ]);

        ExerciseProgression::factory()->create([
            'exercise_id' => $exercise2->id,
            'progression_path_name' => 'push-ups',
            'order' => 2,
            'easier_exercise_id' => $exercise1->id,
            'harder_exercise_id' => $exercise3->id,
        ]);

        ExerciseProgression::factory()->create([
            'exercise_id' => $exercise3->id,
            'progression_path_name' => 'push-ups',
            'order' => 3,
            'easier_exercise_id' => $exercise2->id,
        ]);

        $loadedExercise = Exercise::withProgression()->find($exercise2->id);

        $this->assertTrue($loadedExercise->relationLoaded('progression'));
        $this->assertTrue($loadedExercise->progression->relationLoaded('easierExercise'));
        $this->assertTrue($loadedExercise->progression->relationLoaded('harderExercise'));
        $this->assertEquals('Kneeling Push-ups', $loadedExercise->progression->easierExercise->name);
        $this->assertEquals('Pike Push-ups', $loadedExercise->progression->harderExercise->name);
    }

    public function test_with_progression_handles_exercises_without_progression(): void
    {
        $exerciseWithoutProgression = Exercise::factory()->create(['name' => 'Mountain Climbers']);

        $loadedExercise = Exercise::withProgression()->find($exerciseWithoutProgression->id);

        $this->assertTrue($loadedExercise->relationLoaded('progression'));
        $this->assertNull($loadedExercise->progression);
    }

    public function test_in_progression_path_filters_by_path_name(): void
    {
        $pushUpExercise1 = Exercise::factory()->create(['name' => 'Kneeling Push-ups']);
        $pushUpExercise2 = Exercise::factory()->create(['name' => 'Regular Push-ups']);
        $squatExercise = Exercise::factory()->create(['name' => 'Squats']);
        $standaloneExercise = Exercise::factory()->create(['name' => 'Mountain Climbers']);

        ExerciseProgression::factory()->create([
            'exercise_id' => $pushUpExercise1->id,
            'progression_path_name' => 'push-ups',
            'order' => 1,
        ]);

        ExerciseProgression::factory()->create([
            'exercise_id' => $pushUpExercise2->id,
            'progression_path_name' => 'push-ups',
            'order' => 2,
        ]);

        ExerciseProgression::factory()->create([
            'exercise_id' => $squatExercise->id,
            'progression_path_name' => 'squats',
            'order' => 1,
        ]);

        $pushUpExercises = Exercise::inProgressionPath('push-ups')->get();

        $this->assertCount(2, $pushUpExercises);
        $this->assertTrue($pushUpExercises->contains('id', $pushUpExercise1->id));
        $this->assertTrue($pushUpExercises->contains('id', $pushUpExercise2->id));
        $this->assertFalse($pushUpExercises->contains('id', $squatExercise->id));
        $this->assertFalse($pushUpExercises->contains('id', $standaloneExercise->id));
    }

    public function test_in_progression_path_returns_empty_when_path_not_found(): void
    {
        $exercise = Exercise::factory()->create(['name' => 'Push-ups']);

        ExerciseProgression::factory()->create([
            'exercise_id' => $exercise->id,
            'progression_path_name' => 'push-ups',
        ]);

        $exercises = Exercise::inProgressionPath('non-existent-path')->get();

        $this->assertCount(0, $exercises);
    }

    public function test_scopes_can_be_chained(): void
    {
        $pushUpExercise = Exercise::factory()->create(['name' => 'Push-ups']);
        $squatExercise = Exercise::factory()->create(['name' => 'Squats']);

        ExerciseProgression::factory()->create([
            'exercise_id' => $pushUpExercise->id,
            'progression_path_name' => 'push-ups',
            'order' => 1,
        ]);

        ExerciseProgression::factory()->create([
            'exercise_id' => $squatExercise->id,
            'progression_path_name' => 'squats',
            'order' => 1,
        ]);

        $exercises = Exercise::withProgression()
            ->inProgressionPath('push-ups')
            ->get();

        $this->assertCount(1, $exercises);
        $this->assertEquals($pushUpExercise->id, $exercises->first()->id);
        $this->assertTrue($exercises->first()->relationLoaded('progression'));
    }
}
