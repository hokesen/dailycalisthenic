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

    public function test_go_page_without_template_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/go');

        $response->assertRedirect(route('dashboard'));
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
        $response->assertSee('Push-ups');
        $response->assertSee('Squats');
        $response->assertSee('Start Practice');
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

    public function test_go_page_creates_session_exercise_records(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['name' => 'Test Workout']);
        $exercise1 = Exercise::factory()->create(['name' => 'Push-ups']);
        $exercise2 = Exercise::factory()->create(['name' => 'Squats']);

        $template->exercises()->attach($exercise1->id, [
            'order' => 1,
            'duration_seconds' => 60,
        ]);

        $template->exercises()->attach($exercise2->id, [
            'order' => 2,
            'duration_seconds' => 90,
        ]);

        $this->assertDatabaseCount('session_exercises', 0);

        $response = $this
            ->actingAs($user)
            ->get('/go?template='.$template->id);

        $response->assertOk();
        $this->assertDatabaseCount('session_exercises', 2);

        $session = Session::query()->latest()->first();

        $this->assertDatabaseHas('session_exercises', [
            'session_id' => $session->id,
            'exercise_id' => $exercise1->id,
            'order' => 1,
            'duration_seconds' => 60,
        ]);

        $this->assertDatabaseHas('session_exercises', [
            'session_id' => $session->id,
            'exercise_id' => $exercise2->id,
            'order' => 2,
            'duration_seconds' => 90,
        ]);
    }

    public function test_update_session_with_exercise_completion_data(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create();
        $exercise1 = Exercise::factory()->create(['name' => 'Push-ups']);
        $exercise2 = Exercise::factory()->create(['name' => 'Squats']);

        $template->exercises()->attach($exercise1->id, [
            'order' => 1,
            'duration_seconds' => 60,
        ]);

        $template->exercises()->attach($exercise2->id, [
            'order' => 2,
            'duration_seconds' => 90,
        ]);

        $this
            ->actingAs($user)
            ->get('/go?template='.$template->id);

        $session = Session::query()->latest()->first();

        $response = $this
            ->actingAs($user)
            ->patchJson('/go/'.$session->id.'/update', [
                'status' => 'completed',
                'total_duration_seconds' => 150,
                'exercise_completion' => [
                    [
                        'exercise_id' => $exercise1->id,
                        'order' => 1,
                        'status' => 'completed',
                    ],
                    [
                        'exercise_id' => $exercise2->id,
                        'order' => 2,
                        'status' => 'completed',
                        'duration_seconds' => 30,
                    ],
                ],
            ]);

        $response->assertOk();

        $sessionExercise1 = $session->sessionExercises()
            ->where('exercise_id', $exercise1->id)
            ->first();

        $sessionExercise2 = $session->sessionExercises()
            ->where('exercise_id', $exercise2->id)
            ->first();

        $this->assertNotNull($sessionExercise1->completed_at);
        $this->assertNotNull($sessionExercise2->completed_at);
        $this->assertEquals(30, $sessionExercise2->duration_seconds);
    }

    public function test_update_individual_exercise_completion_in_real_time(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create();
        $exercise1 = Exercise::factory()->create(['name' => 'Push-ups']);
        $exercise2 = Exercise::factory()->create(['name' => 'Squats']);

        $template->exercises()->attach($exercise1->id, [
            'order' => 1,
            'duration_seconds' => 60,
        ]);

        $template->exercises()->attach($exercise2->id, [
            'order' => 2,
            'duration_seconds' => 90,
        ]);

        $this
            ->actingAs($user)
            ->get('/go?template='.$template->id);

        $session = Session::query()->latest()->first();

        // Simulate completing first exercise
        $response = $this
            ->actingAs($user)
            ->patchJson('/go/'.$session->id.'/update', [
                'status' => 'in_progress',
                'total_duration_seconds' => 60,
                'exercise_completion' => [
                    [
                        'exercise_id' => $exercise1->id,
                        'order' => 1,
                        'status' => 'completed',
                    ],
                ],
            ]);

        $response->assertOk();

        $sessionExercise1 = $session->sessionExercises()
            ->where('exercise_id', $exercise1->id)
            ->first();

        $sessionExercise2 = $session->sessionExercises()
            ->where('exercise_id', $exercise2->id)
            ->first();

        // First exercise should be marked completed
        $this->assertNotNull($sessionExercise1->completed_at);
        // Second exercise should still be null (not completed yet)
        $this->assertNull($sessionExercise2->completed_at);

        // Now complete the second exercise with "next" button after partial time
        $response = $this
            ->actingAs($user)
            ->patchJson('/go/'.$session->id.'/update', [
                'status' => 'in_progress',
                'total_duration_seconds' => 105,
                'exercise_completion' => [
                    [
                        'exercise_id' => $exercise2->id,
                        'order' => 2,
                        'status' => 'completed',
                        'duration_seconds' => 45,
                    ],
                ],
            ]);

        $response->assertOk();

        $sessionExercise2->refresh();

        // Second exercise should be marked completed with partial duration
        $this->assertNotNull($sessionExercise2->completed_at);
        $this->assertEquals(45, $sessionExercise2->duration_seconds);
    }

    public function test_next_button_records_actual_duration_spent(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create();
        $exercise = Exercise::factory()->create(['name' => 'Push-ups']);

        $template->exercises()->attach($exercise->id, [
            'order' => 1,
            'duration_seconds' => 60,
        ]);

        $this
            ->actingAs($user)
            ->get('/go?template='.$template->id);

        $session = Session::query()->latest()->first();

        // User presses "next" after 30 seconds of a 60-second exercise
        $response = $this
            ->actingAs($user)
            ->patchJson('/go/'.$session->id.'/update', [
                'status' => 'in_progress',
                'total_duration_seconds' => 30,
                'exercise_completion' => [
                    [
                        'exercise_id' => $exercise->id,
                        'order' => 1,
                        'status' => 'completed',
                        'duration_seconds' => 30,
                    ],
                ],
            ]);

        $response->assertOk();

        $sessionExercise = $session->sessionExercises()
            ->where('exercise_id', $exercise->id)
            ->first();

        // Exercise should be marked as completed
        $this->assertNotNull($sessionExercise->completed_at);
        // Duration should be set to actual time spent (30 seconds)
        $this->assertEquals(30, $sessionExercise->duration_seconds);
    }
}
