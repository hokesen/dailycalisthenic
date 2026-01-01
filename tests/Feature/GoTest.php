<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\Session;
use App\Models\SessionTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoTest extends TestCase
{
    use RefreshDatabase;

    public function test_go_page_without_template_shows_template_selector(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['name' => 'Test Template']);

        $response = $this
            ->actingAs($user)
            ->get('/go');

        $response->assertOk();
        $response->assertSee('Select a Template');
        $response->assertSee('Available Templates');
        $response->assertSee('Test Template');
        $response->assertSee('Go');
    }

    public function test_go_page_with_template_shows_exercises(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['name' => 'Full Body Workout']);
        $exercise1 = Exercise::factory()->create(['name' => 'Push-ups']);
        $exercise2 = Exercise::factory()->create(['name' => 'Squats']);

        $template->exercises()->attach($exercise1->id, [
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
            'duration_seconds' => 60,
            'rest_after_seconds' => 30,
            'notes' => 'Keep form strict',
        ]);

        $template->exercises()->attach($exercise2->id, [
            'order' => 2,
            'sets' => 3,
            'reps' => 15,
            'duration_seconds' => 90,
            'rest_after_seconds' => 45,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/go?template='.$template->id);

        $response->assertOk();
        $response->assertSee('Full Body Workout');
        $response->assertSee('Push-ups');
        $response->assertSee('Squats');
        $response->assertSee('Start Workout');
    }

    public function test_go_page_with_template_creates_session(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['name' => 'Test Workout']);
        $exercise = Exercise::factory()->create(['name' => 'Push-ups']);

        $template->exercises()->attach($exercise->id, [
            'order' => 1,
            'duration_seconds' => 60,
        ]);

        $this->assertDatabaseCount('sessions', 0);

        $response = $this
            ->actingAs($user)
            ->get('/go?template='.$template->id);

        $response->assertOk();
        $this->assertDatabaseCount('sessions', 1);
        $this->assertDatabaseHas('sessions', [
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'name' => 'Test Workout',
            'status' => 'planned',
        ]);
    }

    public function test_go_page_shows_exercises_in_correct_order(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create();
        $exercise1 = Exercise::factory()->create(['name' => 'First Exercise']);
        $exercise2 = Exercise::factory()->create(['name' => 'Second Exercise']);

        $template->exercises()->attach($exercise2->id, ['order' => 2]);
        $template->exercises()->attach($exercise1->id, ['order' => 1]);

        $response = $this
            ->actingAs($user)
            ->get('/go?template='.$template->id);

        $response->assertOk();
        $content = $response->getContent();
        $firstPos = strpos($content, 'First Exercise');
        $secondPos = strpos($content, 'Second Exercise');

        $this->assertLessThan($secondPos, $firstPos, 'Exercises should be ordered correctly');
    }

    public function test_update_session_status_to_in_progress(): void
    {
        $user = User::factory()->create();
        $session = Session::factory()->create([
            'user_id' => $user->id,
            'status' => 'planned',
            'started_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson('/go/'.$session->id.'/update', [
                'status' => 'in_progress',
                'total_duration_seconds' => 0,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('sessions', [
            'id' => $session->id,
            'status' => 'in_progress',
        ]);
        $session->refresh();
        $this->assertNotNull($session->started_at);
    }

    public function test_update_session_status_to_completed(): void
    {
        $user = User::factory()->create();
        $session = Session::factory()->create([
            'user_id' => $user->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson('/go/'.$session->id.'/update', [
                'status' => 'completed',
                'total_duration_seconds' => 600,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('sessions', [
            'id' => $session->id,
            'status' => 'completed',
            'total_duration_seconds' => 600,
        ]);
        $session->refresh();
        $this->assertNotNull($session->completed_at);
    }

    public function test_cannot_update_another_users_session(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $session = Session::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'planned',
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson('/go/'.$session->id.'/update', [
                'status' => 'in_progress',
            ]);

        $response->assertForbidden();
    }

    public function test_update_session_validates_status(): void
    {
        $user = User::factory()->create();
        $session = Session::factory()->create([
            'user_id' => $user->id,
            'status' => 'planned',
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson('/go/'.$session->id.'/update', [
                'status' => 'invalid_status',
            ]);

        $response->assertUnprocessable();
    }

    public function test_go_page_shows_message_when_template_has_no_exercises(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['name' => 'Empty Template']);

        $response = $this
            ->actingAs($user)
            ->get('/go?template='.$template->id);

        $response->assertOk();
        $response->assertSee('No exercises in this template yet.');
    }

    public function test_go_page_only_shows_available_templates(): void
    {
        $user = User::factory()->create();
        $systemTemplate = SessionTemplate::factory()->create(['name' => 'System Template']);
        $userTemplate = SessionTemplate::factory()->create(['user_id' => $user->id, 'name' => 'User Template']);
        $otherUserTemplate = SessionTemplate::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this
            ->actingAs($user)
            ->get('/go');

        $response->assertOk();
        $response->assertSee('System Template');
        $response->assertSee('User Template');
        $response->assertDontSee($otherUserTemplate->name);
    }

    public function test_go_page_returns_404_for_unavailable_template(): void
    {
        $user = User::factory()->create();
        $otherUserTemplate = SessionTemplate::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this
            ->actingAs($user)
            ->get('/go?template='.$otherUserTemplate->id);

        $response->assertNotFound();
    }

    public function test_go_page_returns_404_for_nonexistent_template(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/go?template=99999');

        $response->assertNotFound();
    }

    public function test_go_page_requires_authentication(): void
    {
        $response = $this->get('/go');

        $response->assertRedirect('/login');
    }

    public function test_go_page_template_selector_displays_exercise_details(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['name' => 'Strength Training']);
        $exercise = Exercise::factory()->create(['name' => 'Dips']);

        $template->exercises()->attach($exercise->id, [
            'order' => 1,
            'sets' => 4,
            'reps' => 8,
            'duration_seconds' => 90,
            'rest_after_seconds' => 60,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/go');

        $response->assertOk();
        $response->assertSee('Strength Training');
        $response->assertSee('Dips');
        $response->assertSee('4 × 8');
        $response->assertSee('90s');
        $response->assertSee('Rest: 60s');
    }
}
