<?php

namespace Tests\Feature;

use App\Models\SessionTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_welcome_message_with_user_name(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Welcome, John Doe!');
    }

    public function test_dashboard_displays_available_templates(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $otherUser = User::factory()->create(['name' => 'Jane Smith']);
        $systemTemplate = SessionTemplate::factory()->create(['name' => 'System Template']);
        $userTemplate = SessionTemplate::factory()->create(['user_id' => $user->id, 'name' => 'User Template']);
        $otherUserTemplate = SessionTemplate::factory()->create(['user_id' => $otherUser->id, 'name' => 'Other Template']);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Templates');
        $response->assertSee('System Template');
        $response->assertSee('Default Template');
        $response->assertSee('User Template');
        $response->assertSee('by John Doe');
        $response->assertSee('Other Template');
        $response->assertSee('by Jane Smith');
    }

    public function test_dashboard_shows_copy_button_for_non_owned_templates(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $otherUser = User::factory()->create(['name' => 'Jane Smith']);
        $systemTemplate = SessionTemplate::factory()->create(['name' => 'System Template']);
        $userTemplate = SessionTemplate::factory()->create(['user_id' => $user->id, 'name' => 'User Template']);
        $otherUserTemplate = SessionTemplate::factory()->create(['user_id' => $otherUser->id, 'name' => 'Other Template']);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee(route('templates.copy', $systemTemplate));
        $response->assertSee(route('templates.copy', $otherUserTemplate));
        $response->assertDontSee(route('templates.copy', $userTemplate));
    }

    public function test_dashboard_shows_message_when_no_templates_available(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('No workout templates available yet.');
    }

    public function test_dashboard_displays_template_details(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create([
            'name' => 'Full Body Workout',
            'description' => 'A comprehensive workout',
        ]);
        $exercise1 = \App\Models\Exercise::factory()->create(['name' => 'Push-ups']);
        $exercise2 = \App\Models\Exercise::factory()->create(['name' => 'Squats']);

        $template->exercises()->attach($exercise1->id, [
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
            'duration_seconds' => 60,
            'rest_after_seconds' => 30,
        ]);
        $template->exercises()->attach($exercise2->id, [
            'order' => 2,
            'sets' => 3,
            'reps' => 15,
            'duration_seconds' => 90,
            'rest_after_seconds' => 30,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Full Body Workout');
        $response->assertSee('A comprehensive workout');
        $response->assertSee('Push-ups');
        $response->assertSee('Squats');
        $response->assertSee('3 × 10');
        $response->assertSee('60s');
        $response->assertSee('Rest: 30s');
        $response->assertSee('3 × 15');
        $response->assertSee('90s');
        $response->assertSee('~4 minutes');
    }

    public function test_dashboard_displays_go_buttons_for_templates(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['name' => 'Test Template']);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee(route('go.index', ['template' => $template->id]));
        $response->assertSee('Go');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_dashboard_displays_exercise_details_for_time_based_exercises(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['name' => 'Cardio Session']);
        $exercise = \App\Models\Exercise::factory()->create(['name' => 'Jumping Jacks']);

        $template->exercises()->attach($exercise->id, [
            'order' => 1,
            'duration_seconds' => 120,
            'rest_after_seconds' => 45,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Jumping Jacks');
        $response->assertSee('120s');
        $response->assertSee('Rest: 45s');
    }

    public function test_dashboard_displays_activity_calendar_section(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Your Activity');
        $response->assertSee('Current Streak');
    }

    public function test_dashboard_displays_current_streak(): void
    {
        $user = User::factory()->create();

        // Create sessions for the last 3 days
        for ($i = 0; $i < 3; $i++) {
            \App\Models\Session::factory()->create([
                'user_id' => $user->id,
                'status' => \App\Enums\SessionStatus::Completed,
                'completed_at' => now()->subDays($i),
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('3 days');
        $response->assertSee('Current Streak');
    }

    public function test_dashboard_displays_zero_streak_when_no_recent_sessions(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('0 days');
        $response->assertSee('Current Streak');
    }

    public function test_dashboard_displays_past_week_calendar(): void
    {
        $user = User::factory()->create();

        // Create a session for today
        \App\Models\Session::factory()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        // Check for day names (Mon, Tue, etc.)
        $response->assertSee(now()->format('D'));
    }

    public function test_weekly_exercise_breakdown_returns_correct_structure(): void
    {
        $user = User::factory()->create();
        $exercise = \App\Models\Exercise::factory()->create(['name' => 'Plank']);
        $session = \App\Models\Session::factory()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\SessionStatus::Completed,
            'completed_at' => now(),
        ]);
        \App\Models\SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'duration_seconds' => 300,
        ]);

        $breakdown = $user->getWeeklyExerciseBreakdown(7);

        $this->assertCount(7, $breakdown);
        foreach ($breakdown as $day) {
            $this->assertArrayHasKey('date', $day);
            $this->assertArrayHasKey('dayName', $day);
            $this->assertArrayHasKey('hasSession', $day);
            $this->assertArrayHasKey('exercises', $day);
        }
    }

    public function test_weekly_exercise_breakdown_aggregates_multiple_exercises_same_day(): void
    {
        $user = User::factory()->create();
        $exercise1 = \App\Models\Exercise::factory()->create(['name' => 'Plank']);
        $exercise2 = \App\Models\Exercise::factory()->create(['name' => 'Push-ups']);

        $session = \App\Models\Session::factory()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        \App\Models\SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise1->id,
            'duration_seconds' => 300,
        ]);

        \App\Models\SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise2->id,
            'duration_seconds' => 180,
        ]);

        $breakdown = $user->getWeeklyExerciseBreakdown(7);
        $today = collect($breakdown)->first(fn ($day) => $day['hasSession']);

        $this->assertCount(2, $today['exercises']);
        $this->assertEquals('Plank', $today['exercises'][0]['name']);
        $this->assertEquals(300, $today['exercises'][0]['total_seconds']);
        $this->assertEquals('Push-ups', $today['exercises'][1]['name']);
        $this->assertEquals(180, $today['exercises'][1]['total_seconds']);
    }

    public function test_weekly_progression_summary_shows_worked_exercise_and_harder_variations(): void
    {
        $user = User::factory()->create();
        $exercise1 = \App\Models\Exercise::factory()->create(['name' => 'Kneeling Push-ups']);
        $exercise2 = \App\Models\Exercise::factory()->create(['name' => 'Regular Push-ups']);
        $exercise3 = \App\Models\Exercise::factory()->create(['name' => 'Pike Push-ups']);

        // Create progression chain: exercise1 -> exercise2 -> exercise3
        $progression1 = \App\Models\ExerciseProgression::factory()->create([
            'exercise_id' => $exercise1->id,
            'harder_exercise_id' => $exercise2->id,
            'progression_path_name' => 'push-ups',
        ]);

        $progression2 = \App\Models\ExerciseProgression::factory()->create([
            'exercise_id' => $exercise2->id,
            'easier_exercise_id' => $exercise1->id,
            'harder_exercise_id' => $exercise3->id,
            'progression_path_name' => 'push-ups',
        ]);

        $progression3 = \App\Models\ExerciseProgression::factory()->create([
            'exercise_id' => $exercise3->id,
            'easier_exercise_id' => $exercise2->id,
            'progression_path_name' => 'push-ups',
        ]);

        // Create session data for only exercise1
        $session = \App\Models\Session::factory()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        \App\Models\SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise1->id,
            'duration_seconds' => 900,
        ]);

        $summary = $user->getWeeklyProgressionSummary(7);

        $this->assertCount(1, $summary);
        $this->assertEquals('push-ups', $summary[0]['path_name']);
        $this->assertCount(3, $summary[0]['exercises']); // Worked exercise + 2 harder variations
        $this->assertEquals('Kneeling Push-ups', $summary[0]['exercises'][0]['name']);
        $this->assertEquals(900, $summary[0]['exercises'][0]['total_seconds']);
        $this->assertEquals('Regular Push-ups', $summary[0]['exercises'][1]['name']);
        $this->assertEquals(0, $summary[0]['exercises'][1]['total_seconds']); // Not worked on
        $this->assertEquals('Pike Push-ups', $summary[0]['exercises'][2]['name']);
        $this->assertEquals(0, $summary[0]['exercises'][2]['total_seconds']); // Not worked on
    }

    public function test_weekly_progression_summary_only_shows_worked_on_paths(): void
    {
        $user = User::factory()->create();
        $workedExercise = \App\Models\Exercise::factory()->create(['name' => 'Plank']);
        $notWorkedExercise = \App\Models\Exercise::factory()->create(['name' => 'Squats']);

        \App\Models\ExerciseProgression::factory()->create([
            'exercise_id' => $workedExercise->id,
            'progression_path_name' => 'plank',
        ]);

        \App\Models\ExerciseProgression::factory()->create([
            'exercise_id' => $notWorkedExercise->id,
            'progression_path_name' => 'squats',
        ]);

        // Create session for plank only
        $session = \App\Models\Session::factory()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        \App\Models\SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $workedExercise->id,
            'duration_seconds' => 300,
        ]);

        $summary = $user->getWeeklyProgressionSummary(7);

        $this->assertCount(1, $summary);
        $this->assertEquals('plank', $summary[0]['path_name']);
    }

    public function test_dashboard_renders_with_new_activity_data(): void
    {
        $user = User::factory()->create();
        $exercise = \App\Models\Exercise::factory()->create(['name' => 'Test Exercise']);

        $session = \App\Models\Session::factory()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        \App\Models\SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'duration_seconds' => 300,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Past Week');
    }

    public function test_dashboard_displays_progression_summary_when_available(): void
    {
        $user = User::factory()->create();
        $exercise = \App\Models\Exercise::factory()->create(['name' => 'Kneeling Plank']);

        \App\Models\ExerciseProgression::factory()->create([
            'exercise_id' => $exercise->id,
            'progression_path_name' => 'plank',
        ]);

        $session = \App\Models\Session::factory()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        \App\Models\SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'duration_seconds' => 300,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Weekly Progression Summary');
        $response->assertSee('Plank Progression');
    }

    public function test_dashboard_hides_progression_summary_when_no_progressions(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('Weekly Progression Summary');
    }
}
