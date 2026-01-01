<?php

namespace Tests\Feature;

use App\Filament\Imports\SessionTemplateImporter;
use App\Models\Exercise;
use App\Models\SessionTemplate;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTemplateImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_template_export_includes_exercises(): void
    {
        $template = SessionTemplate::factory()->create([
            'name' => 'Test Template',
            'default_rest_seconds' => 60,
        ]);

        $exercise1 = Exercise::factory()->create(['name' => 'Push-ups']);
        $exercise2 = Exercise::factory()->create(['name' => 'Pull-ups']);

        $template->exercises()->attach($exercise1->id, [
            'order' => 1,
            'duration_seconds' => 60,
            'rest_after_seconds' => 30,
            'sets' => 3,
            'reps' => 10,
            'notes' => 'First exercise',
        ]);

        $template->exercises()->attach($exercise2->id, [
            'order' => 2,
            'duration_seconds' => 90,
            'rest_after_seconds' => 45,
            'sets' => 4,
            'reps' => 8,
            'notes' => 'Second exercise',
        ]);

        $template->refresh();

        $exercises = $template->exercises->map(function ($exercise) {
            return [
                'exercise_id' => $exercise->id,
                'exercise_name' => $exercise->name,
                'order' => $exercise->pivot->order,
                'duration_seconds' => $exercise->pivot->duration_seconds,
                'rest_after_seconds' => $exercise->pivot->rest_after_seconds,
                'sets' => $exercise->pivot->sets,
                'reps' => $exercise->pivot->reps,
                'notes' => $exercise->pivot->notes,
            ];
        })->toArray();

        $exportedJson = json_encode($exercises);

        $exercisesData = json_decode($exportedJson, true);

        $this->assertIsArray($exercisesData);
        $this->assertCount(2, $exercisesData);

        $this->assertEquals($exercise1->id, $exercisesData[0]['exercise_id']);
        $this->assertEquals('Push-ups', $exercisesData[0]['exercise_name']);
        $this->assertEquals(1, $exercisesData[0]['order']);
        $this->assertEquals(60, $exercisesData[0]['duration_seconds']);
        $this->assertEquals(30, $exercisesData[0]['rest_after_seconds']);
        $this->assertEquals(3, $exercisesData[0]['sets']);
        $this->assertEquals(10, $exercisesData[0]['reps']);
        $this->assertEquals('First exercise', $exercisesData[0]['notes']);

        $this->assertEquals($exercise2->id, $exercisesData[1]['exercise_id']);
        $this->assertEquals('Pull-ups', $exercisesData[1]['exercise_name']);
        $this->assertEquals(2, $exercisesData[1]['order']);
    }

    public function test_session_template_import_creates_exercise_relationships(): void
    {
        $user = User::factory()->create();
        $exercise1 = Exercise::factory()->create(['name' => 'Squats']);
        $exercise2 = Exercise::factory()->create(['name' => 'Lunges']);

        $exercisesJson = json_encode([
            [
                'exercise_id' => $exercise1->id,
                'exercise_name' => 'Squats',
                'order' => 1,
                'duration_seconds' => 120,
                'rest_after_seconds' => 60,
                'sets' => 5,
                'reps' => 15,
                'notes' => 'Deep squats',
            ],
            [
                'exercise_id' => $exercise2->id,
                'exercise_name' => 'Lunges',
                'order' => 2,
                'duration_seconds' => 90,
                'rest_after_seconds' => 45,
                'sets' => 3,
                'reps' => 12,
                'notes' => 'Alternating lunges',
            ],
        ]);

        $import = Import::create([
            'user_id' => $user->id,
            'file_name' => 'test.csv',
            'file_path' => 'test.csv',
            'importer' => SessionTemplateImporter::class,
            'total_rows' => 1,
            'processed_rows' => 0,
            'successful_rows' => 0,
        ]);

        $importer = new SessionTemplateImporter($import, [], []);

        $reflection = new \ReflectionClass($importer);
        $dataProperty = $reflection->getProperty('data');
        $dataProperty->setValue($importer, [
            'name' => 'Leg Day',
            'description' => 'Lower body workout',
            'default_rest_seconds' => 60,
            'exercises' => json_decode($exercisesJson, true),
        ]);

        $template = $importer->resolveRecord();
        $template->save();

        $recordProperty = $reflection->getProperty('record');
        $recordProperty->setValue($importer, $template);

        $method = $reflection->getMethod('afterSave');
        $method->invoke($importer);

        $template->refresh();

        $this->assertCount(2, $template->exercises);

        $firstExercise = $template->exercises->firstWhere('id', $exercise1->id);
        $this->assertNotNull($firstExercise);
        $this->assertEquals(1, $firstExercise->pivot->order);
        $this->assertEquals(120, $firstExercise->pivot->duration_seconds);
        $this->assertEquals(60, $firstExercise->pivot->rest_after_seconds);
        $this->assertEquals(5, $firstExercise->pivot->sets);
        $this->assertEquals(15, $firstExercise->pivot->reps);
        $this->assertEquals('Deep squats', $firstExercise->pivot->notes);

        $secondExercise = $template->exercises->firstWhere('id', $exercise2->id);
        $this->assertNotNull($secondExercise);
        $this->assertEquals(2, $secondExercise->pivot->order);
        $this->assertEquals(90, $secondExercise->pivot->duration_seconds);
        $this->assertEquals(45, $secondExercise->pivot->rest_after_seconds);
        $this->assertEquals(3, $secondExercise->pivot->sets);
        $this->assertEquals(12, $secondExercise->pivot->reps);
        $this->assertEquals('Alternating lunges', $secondExercise->pivot->notes);
    }

    public function test_import_handles_empty_exercises(): void
    {
        $user = User::factory()->create();

        $import = Import::create([
            'user_id' => $user->id,
            'file_name' => 'test.csv',
            'file_path' => 'test.csv',
            'importer' => SessionTemplateImporter::class,
            'total_rows' => 1,
            'processed_rows' => 0,
            'successful_rows' => 0,
        ]);

        $importer = new SessionTemplateImporter($import, [], []);

        $reflection = new \ReflectionClass($importer);
        $dataProperty = $reflection->getProperty('data');
        $dataProperty->setValue($importer, [
            'name' => 'Empty Template',
            'default_rest_seconds' => 60,
            'exercises' => null,
        ]);

        $template = $importer->resolveRecord();
        $template->save();

        $recordProperty = $reflection->getProperty('record');
        $recordProperty->setValue($importer, $template);

        $method = $reflection->getMethod('afterSave');
        $method->invoke($importer);

        $template->refresh();

        $this->assertCount(0, $template->exercises);
    }

    public function test_import_skips_invalid_exercise_ids(): void
    {
        $user = User::factory()->create();
        $validExercise = Exercise::factory()->create(['name' => 'Valid Exercise']);

        $exercisesJson = json_encode([
            [
                'exercise_id' => 999999,
                'exercise_name' => 'Non-existent Exercise',
                'order' => 1,
                'duration_seconds' => 60,
            ],
            [
                'exercise_id' => $validExercise->id,
                'exercise_name' => 'Valid Exercise',
                'order' => 2,
                'duration_seconds' => 90,
                'sets' => 3,
                'reps' => 10,
            ],
        ]);

        $import = Import::create([
            'user_id' => $user->id,
            'file_name' => 'test.csv',
            'file_path' => 'test.csv',
            'importer' => SessionTemplateImporter::class,
            'total_rows' => 1,
            'processed_rows' => 0,
            'successful_rows' => 0,
        ]);

        $importer = new SessionTemplateImporter($import, [], []);

        $reflection = new \ReflectionClass($importer);
        $dataProperty = $reflection->getProperty('data');
        $dataProperty->setValue($importer, [
            'name' => 'Test Template',
            'default_rest_seconds' => 60,
            'exercises' => json_decode($exercisesJson, true),
        ]);

        $template = $importer->resolveRecord();
        $template->save();

        $recordProperty = $reflection->getProperty('record');
        $recordProperty->setValue($importer, $template);

        $method = $reflection->getMethod('afterSave');
        $method->invoke($importer);

        $template->refresh();

        $this->assertCount(1, $template->exercises);
        $this->assertEquals($validExercise->id, $template->exercises->first()->id);
    }
}
