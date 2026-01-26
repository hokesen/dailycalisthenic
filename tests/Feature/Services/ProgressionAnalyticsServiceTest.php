<?php

namespace Tests\Feature\Services;

use App\Enums\SessionStatus;
use App\Models\Exercise;
use App\Models\ExerciseProgression;
use App\Models\Session;
use App\Models\SessionExercise;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Services\ProgressionAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressionAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgressionAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $streakService = new \App\Services\StreakService;
        $this->service = new ProgressionAnalyticsService($streakService);
    }

    public function test_get_weekly_progression_summary_returns_empty_when_no_sessions(): void
    {
        $user = User::factory()->create();

        $result = $this->service->getWeeklyProgressionSummary($user, 7);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_weekly_progression_summary_shows_worked_exercise_and_harder_variations(): void
    {
        $user = User::factory()->create();

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

        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $session = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
            'total_duration_seconds' => 900,
        ]);

        SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise1->id,
            'duration_seconds' => 900,
        ]);

        $summary = $this->service->getWeeklyProgressionSummary($user, 7);

        $this->assertCount(1, $summary);
        $this->assertEquals('push-ups', $summary[0]['path_name']);
        $this->assertCount(3, $summary[0]['exercises']);
        $this->assertEquals('Kneeling Push-ups', $summary[0]['exercises'][0]['name']);
        $this->assertEquals(900, $summary[0]['exercises'][0]['total_seconds']);
        $this->assertEquals('Regular Push-ups', $summary[0]['exercises'][1]['name']);
        $this->assertEquals(0, $summary[0]['exercises'][1]['total_seconds']);
    }

    public function test_get_weekly_progression_summary_only_shows_worked_on_paths(): void
    {
        $user = User::factory()->create();
        $workedExercise = Exercise::factory()->create(['name' => 'Plank']);
        $notWorkedExercise = Exercise::factory()->create(['name' => 'Squats']);

        ExerciseProgression::factory()->create([
            'exercise_id' => $workedExercise->id,
            'progression_path_name' => 'plank',
        ]);

        ExerciseProgression::factory()->create([
            'exercise_id' => $notWorkedExercise->id,
            'progression_path_name' => 'squats',
        ]);

        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $session = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
            'total_duration_seconds' => 300,
        ]);

        SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $workedExercise->id,
            'duration_seconds' => 300,
        ]);

        $summary = $this->service->getWeeklyProgressionSummary($user, 7);

        $this->assertCount(1, $summary);
        $this->assertEquals('plank', $summary[0]['path_name']);
    }

    public function test_get_weekly_standalone_exercises_returns_empty_when_no_sessions(): void
    {
        $user = User::factory()->create();

        $result = $this->service->getWeeklyStandaloneExercises($user, 7);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_weekly_standalone_exercises_returns_exercises_without_progression(): void
    {
        $user = User::factory()->create();
        $standaloneExercise = Exercise::factory()->create(['name' => 'Mountain Climbers']);

        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $session = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
            'total_duration_seconds' => 420,
        ]);

        SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $standaloneExercise->id,
            'duration_seconds' => 420,
        ]);

        $standaloneExercises = $this->service->getWeeklyStandaloneExercises($user, 7);

        $this->assertCount(1, $standaloneExercises);
        $this->assertEquals('Mountain Climbers', $standaloneExercises[0]['name']);
        $this->assertEquals(420, $standaloneExercises[0]['total_seconds']);
    }

    public function test_get_weekly_standalone_exercises_excludes_progression_exercises(): void
    {
        $user = User::factory()->create();
        $progressionExercise = Exercise::factory()->create(['name' => 'Push-ups']);
        $standaloneExercise = Exercise::factory()->create(['name' => 'Mountain Climbers']);

        ExerciseProgression::factory()->create([
            'exercise_id' => $progressionExercise->id,
            'progression_path_name' => 'push-ups',
        ]);

        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $session = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
            'total_duration_seconds' => 600,
        ]);

        SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $progressionExercise->id,
            'duration_seconds' => 180,
        ]);

        SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $standaloneExercise->id,
            'duration_seconds' => 420,
        ]);

        $standaloneExercises = $this->service->getWeeklyStandaloneExercises($user, 7);

        $this->assertCount(1, $standaloneExercises);
        $this->assertEquals('Mountain Climbers', $standaloneExercises[0]['name']);
    }

    public function test_get_progression_gantt_data_returns_correct_structure(): void
    {
        $user = User::factory()->create();

        $result = $this->service->getProgressionGanttData($user, 7);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('progressions', $result);
        $this->assertArrayHasKey('standalone', $result);
        $this->assertArrayHasKey('dayLabels', $result);
        $this->assertArrayHasKey('dailyTotals', $result);
        $this->assertArrayHasKey('weeklyTotal', $result);
    }

    public function test_get_progression_gantt_data_returns_empty_when_no_sessions(): void
    {
        $user = User::factory()->create();

        $result = $this->service->getProgressionGanttData($user, 7);

        $this->assertEmpty($result['progressions']);
        $this->assertEmpty($result['standalone']);
        $this->assertEquals(0, $result['weeklyTotal']);
    }

    public function test_get_progression_gantt_data_includes_daily_breakdown(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->create(['name' => 'Push-ups']);

        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $session = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'started_at' => now(),
            'completed_at' => now(),
            'total_duration_seconds' => 300,
        ]);

        SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'duration_seconds' => 300,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $result = $this->service->getProgressionGanttData($user, 7);

        $this->assertCount(7, $result['dayLabels']);
        $this->assertCount(7, $result['dailyTotals']);
        $this->assertEquals(300, $result['weeklyTotal']);
    }
}
