<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\User;
use App\Repositories\ExerciseRepository;
use App\Services\DefaultExerciseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultExercisesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear the cache before each test
        app(ExerciseRepository::class)->clearCache();
    }

    public function test_default_exercise_service_loads_exercises_from_json(): void
    {
        $service = app(DefaultExerciseService::class);

        $exercises = $service->getDefaultExercises();

        $this->assertNotEmpty($exercises);
        $this->assertTrue($exercises->has('Push Ups'));
        $this->assertTrue($exercises->has('Plank'));
        $this->assertTrue($exercises->has('Squat'));
    }

    public function test_default_exercise_service_loads_progressions_from_json(): void
    {
        $service = app(DefaultExerciseService::class);

        $progressions = $service->getProgressions();

        $this->assertNotEmpty($progressions);

        $pushUpPath = $progressions->firstWhere('path_name', 'Push-Up');
        $this->assertNotNull($pushUpPath);
        $this->assertContains('Wall Push-Up', $pushUpPath['exercises']);
        $this->assertContains('Push Ups', $pushUpPath['exercises']);
    }

    public function test_default_exercise_service_gets_progression_for_exercise(): void
    {
        $service = app(DefaultExerciseService::class);

        $progression = $service->getProgressionForExercise('Push Ups');

        $this->assertNotNull($progression);
        $this->assertEquals('Push-Up', $progression['path_name']);
        $this->assertEquals('Kneeling Push-Up', $progression['easier']);
        $this->assertEquals('Archer Push-Ups', $progression['harder']);
    }

    public function test_exercise_repository_returns_default_exercises(): void
    {
        $repository = app(ExerciseRepository::class);

        $exercises = $repository->getAvailableForUser();

        $this->assertNotEmpty($exercises);

        // Should have default exercises
        $pushUp = $exercises->firstWhere('name', 'Push Ups');
        $this->assertNotNull($pushUp);
        $this->assertInstanceOf(Exercise::class, $pushUp);
    }

    public function test_exercise_repository_excludes_db_exercises_with_same_name_as_defaults(): void
    {
        // Create a database exercise with same name as a default
        Exercise::factory()->create([
            'user_id' => null,
            'name' => 'Push Ups',
            'description' => 'Database version',
        ]);

        $repository = app(ExerciseRepository::class);
        $exercises = $repository->getAvailableForUser();

        // Should only have one Push Ups (the default)
        $pushUps = $exercises->where('name', 'Push Ups');
        $this->assertCount(1, $pushUps);

        // And it should be the default (with description from JSON)
        $pushUp = $pushUps->first();
        $this->assertNotEquals('Database version', $pushUp->description);
    }

    public function test_exercise_repository_includes_user_custom_exercises(): void
    {
        $user = User::factory()->create();

        Exercise::factory()->create([
            'user_id' => $user->id,
            'name' => 'My Custom Exercise',
        ]);

        $repository = app(ExerciseRepository::class);
        $exercises = $repository->getAvailableForUser($user);

        $customExercise = $exercises->firstWhere('name', 'My Custom Exercise');
        $this->assertNotNull($customExercise);
    }

    public function test_exercise_repository_materializes_default_exercise(): void
    {
        $repository = app(ExerciseRepository::class);

        // Get defaults to find a negative ID
        $defaults = $repository->getDefaultExercisesAsModels();
        $pushUp = $defaults->firstWhere('name', 'Push Ups');

        $this->assertLessThan(0, $pushUp->id);
        $this->assertFalse($pushUp->exists);

        // Materialize it
        $materialized = $repository->materialize($pushUp->id);

        $this->assertNotNull($materialized);
        $this->assertGreaterThan(0, $materialized->id);
        $this->assertTrue($materialized->exists);
        $this->assertEquals('Push Ups', $materialized->name);

        // Should be in database now
        $this->assertDatabaseHas('exercises', [
            'name' => 'Push Ups',
            'user_id' => null,
        ]);
    }

    public function test_exercise_repository_gets_easier_variations_from_defaults(): void
    {
        $repository = app(ExerciseRepository::class);

        $defaults = $repository->getDefaultExercisesAsModels();
        $pushUp = $defaults->firstWhere('name', 'Push Ups');

        $easierVariations = $repository->getEasierVariations($pushUp);

        $this->assertNotEmpty($easierVariations);
        $this->assertEquals('Kneeling Push-Up', $easierVariations->first()->name);
    }

    public function test_exercise_repository_gets_harder_variations_from_defaults(): void
    {
        $repository = app(ExerciseRepository::class);

        $defaults = $repository->getDefaultExercisesAsModels();
        $pushUp = $defaults->firstWhere('name', 'Push Ups');

        $harderVariations = $repository->getHarderVariations($pushUp);

        $this->assertNotEmpty($harderVariations);
        $this->assertEquals('Archer Push-Ups', $harderVariations->first()->name);
    }

    public function test_default_exercises_have_correct_attributes(): void
    {
        $repository = app(ExerciseRepository::class);

        $defaults = $repository->getDefaultExercisesAsModels();
        $pushUp = $defaults->firstWhere('name', 'Push Ups');

        $this->assertNotNull($pushUp->description);
        $this->assertNotNull($pushUp->instructions);
        $this->assertNotNull($pushUp->category);
        $this->assertNotNull($pushUp->difficulty_level);
    }

    public function test_dashboard_shows_default_exercises_in_add_dropdown(): void
    {
        $user = User::factory()->create();

        // Create a template so the add exercise dropdown is rendered
        $template = \App\Models\SessionTemplate::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/');

        $response->assertOk();
        // Default exercises should appear in the add exercise dropdown
        $response->assertSee('Push Ups');
        $response->assertSee('Plank');
    }
}
