<?php

namespace Tests\Feature\Models;

use App\Enums\SessionStatus;
use App\Models\Exercise;
use App\Models\Session;
use App\Models\SessionTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_date_range_filters_sessions_within_range(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $start = Carbon::parse('2024-01-01 00:00:00');
        $end = Carbon::parse('2024-01-07 23:59:59');

        // Session within range
        $sessionInRange = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => Carbon::parse('2024-01-03 12:00:00'),
        ]);

        // Session before range
        $sessionBefore = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => Carbon::parse('2023-12-31 23:59:59'),
        ]);

        // Session after range
        $sessionAfter = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => Carbon::parse('2024-01-08 00:00:01'),
        ]);

        $sessions = Session::forDateRange($start, $end)->get();

        $this->assertCount(1, $sessions);
        $this->assertEquals($sessionInRange->id, $sessions->first()->id);
    }

    public function test_for_date_range_includes_boundary_dates(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $start = Carbon::parse('2024-01-01 00:00:00');
        $end = Carbon::parse('2024-01-07 23:59:59');

        // Session at start boundary
        $sessionAtStart = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => Carbon::parse('2024-01-01 00:00:00'),
        ]);

        // Session at end boundary
        $sessionAtEnd = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => Carbon::parse('2024-01-07 23:59:59'),
        ]);

        $sessions = Session::forDateRange($start, $end)->get();

        $this->assertCount(2, $sessions);
        $this->assertTrue($sessions->contains('id', $sessionAtStart->id));
        $this->assertTrue($sessions->contains('id', $sessionAtEnd->id));
    }

    public function test_with_exercises_eager_loads_session_exercises_and_exercises(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $exercise1 = Exercise::factory()->create(['name' => 'Push-ups']);
        $exercise2 = Exercise::factory()->create(['name' => 'Squats']);

        $session = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        $session->sessionExercises()->create([
            'exercise_id' => $exercise1->id,
            'order' => 1,
            'duration_seconds' => 60,
        ]);

        $session->sessionExercises()->create([
            'exercise_id' => $exercise2->id,
            'order' => 2,
            'duration_seconds' => 90,
        ]);

        // Query with the scope
        $loadedSession = Session::withExercises()->find($session->id);

        // Check that relationships are loaded
        $this->assertTrue($loadedSession->relationLoaded('sessionExercises'));
        $this->assertCount(2, $loadedSession->sessionExercises);
        $this->assertTrue($loadedSession->sessionExercises->first()->relationLoaded('exercise'));
        $this->assertEquals('Push-ups', $loadedSession->sessionExercises->first()->exercise->name);
    }

    public function test_with_exercises_returns_empty_collection_when_no_exercises(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $session = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        $loadedSession = Session::withExercises()->find($session->id);

        $this->assertTrue($loadedSession->relationLoaded('sessionExercises'));
        $this->assertCount(0, $loadedSession->sessionExercises);
    }

    public function test_scopes_can_be_chained(): void
    {
        $user = User::factory()->create();
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        $start = Carbon::parse('2024-01-01 00:00:00');
        $end = Carbon::parse('2024-01-07 23:59:59');

        // Completed session within range
        $completedInRange = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => Carbon::parse('2024-01-03 12:00:00'),
        ]);

        // Incomplete session within range
        $incompleteInRange = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::InProgress,
            'completed_at' => null,
        ]);

        // Completed session outside range
        $completedOutside = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => Carbon::parse('2024-01-10 12:00:00'),
        ]);

        $sessions = Session::completed()
            ->forDateRange($start, $end)
            ->get();

        $this->assertCount(1, $sessions);
        $this->assertEquals($completedInRange->id, $sessions->first()->id);
    }
}
