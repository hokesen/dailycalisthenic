<?php

namespace Tests\Feature\Services;

use App\Enums\SessionStatus;
use App\Models\Session;
use App\Models\SessionExercise;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Services\UserActivityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserActivityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserActivityService;
    }

    public function test_has_practiced_today_returns_true_when_user_has_completed_session_today(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        $result = $this->service->hasPracticedToday($user);

        $this->assertTrue($result);
    }

    public function test_has_practiced_today_returns_false_when_user_has_no_sessions(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        $result = $this->service->hasPracticedToday($user);

        $this->assertFalse($result);
    }

    public function test_has_practiced_today_returns_false_when_session_was_yesterday(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC')->subDay(),
            'total_duration_seconds' => 300,
        ]);

        $result = $this->service->hasPracticedToday($user);

        $this->assertFalse($result);
    }

    public function test_get_past_days_with_activity_returns_correct_structure(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        $result = $this->service->getPastDaysWithActivity($user, 7);

        $this->assertCount(7, $result);
        $this->assertArrayHasKey('date', $result[0]);
        $this->assertArrayHasKey('hasSession', $result[0]);
        $this->assertArrayHasKey('dayName', $result[0]);
        $this->assertInstanceOf(Carbon::class, $result[0]['date']);
    }

    public function test_get_past_days_with_activity_marks_today_as_having_session(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        $result = $this->service->getPastDaysWithActivity($user, 7);

        $this->assertTrue($result[6]['hasSession']);
    }

    public function test_get_weekly_exercise_breakdown_returns_correct_structure(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $session = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        SessionExercise::factory()->create([
            'session_id' => $session->id,
            'duration_seconds' => 120,
        ]);

        $result = $this->service->getWeeklyExerciseBreakdown($user, 7);

        $this->assertCount(7, $result);
        $this->assertArrayHasKey('date', $result[0]);
        $this->assertArrayHasKey('dayName', $result[0]);
        $this->assertArrayHasKey('hasSession', $result[0]);
        $this->assertArrayHasKey('exercises', $result[0]);
    }

    public function test_get_weekly_exercise_breakdown_aggregates_exercise_durations(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);

        // Set a specific time that won't cross midnight when adding 1 hour
        $baseTime = $user->now()->setTime(12, 0, 0); // Noon in user's timezone

        $session1 = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $baseTime->copy()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        $session2 = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $baseTime->copy()->addHour()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        $exercise = \App\Models\Exercise::factory()->create(['name' => 'Push-ups']);

        SessionExercise::factory()->create([
            'session_id' => $session1->id,
            'exercise_id' => $exercise->id,
            'duration_seconds' => 120,
        ]);

        SessionExercise::factory()->create([
            'session_id' => $session2->id,
            'exercise_id' => $exercise->id,
            'duration_seconds' => 180,
        ]);

        $result = $this->service->getWeeklyExerciseBreakdown($user, 7);

        $todayData = $result[6];
        $this->assertTrue($todayData['hasSession']);
        $this->assertCount(1, $todayData['exercises']);
        $this->assertEquals('Push-ups', $todayData['exercises'][0]['name']);
        $this->assertEquals(300, $todayData['exercises'][0]['total_seconds']);
    }
}
