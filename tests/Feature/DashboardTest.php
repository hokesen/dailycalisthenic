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
        $user = User::factory()->create();
        $systemTemplate = SessionTemplate::factory()->create(['name' => 'System Template']);
        $userTemplate = SessionTemplate::factory()->create(['user_id' => $user->id, 'name' => 'User Template']);
        $otherUserTemplate = SessionTemplate::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Available Templates');
        $response->assertSee('System Template');
        $response->assertSee('User Template');
        $response->assertDontSee($otherUserTemplate->name);
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
}
