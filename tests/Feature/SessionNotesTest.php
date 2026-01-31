<?php

namespace Tests\Feature;

use App\Models\Session;
use App\Models\SessionExercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_notes_to_completed_session(): void
    {
        $user = User::factory()->create();
        $session = Session::factory()->completed()->create([
            'user_id' => $user->id,
            'notes' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('sessions.update-notes', $session), [
                'notes' => 'Great practice today!',
            ]);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('sessions', [
            'id' => $session->id,
            'notes' => 'Great practice today!',
        ]);
    }

    public function test_user_can_update_session_notes(): void
    {
        $user = User::factory()->create();
        $session = Session::factory()->completed()->create([
            'user_id' => $user->id,
            'notes' => 'Original notes',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('sessions.update-notes', $session), [
                'notes' => 'Updated notes',
            ]);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('sessions', [
            'id' => $session->id,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_user_cannot_edit_other_users_sessions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $session = Session::factory()->completed()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('sessions.update-notes', $session), [
                'notes' => 'Trying to update',
            ]);

        $response->assertForbidden();
    }

    public function test_user_can_add_notes_to_session_exercise(): void
    {
        $user = User::factory()->create();
        $session = Session::factory()->completed()->create([
            'user_id' => $user->id,
        ]);
        $exercise = SessionExercise::factory()->completed()->create([
            'session_id' => $session->id,
            'notes' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('sessions.update-exercise-notes', [$session, $exercise]), [
                'notes' => 'Felt challenging today',
            ]);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('session_exercises', [
            'id' => $exercise->id,
            'notes' => 'Felt challenging today',
        ]);
    }

    public function test_user_can_clear_session_notes(): void
    {
        $user = User::factory()->create();
        $session = Session::factory()->completed()->create([
            'user_id' => $user->id,
            'notes' => 'Some notes',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('sessions.update-notes', $session), [
                'notes' => null,
            ]);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('sessions', [
            'id' => $session->id,
            'notes' => null,
        ]);
    }
}
