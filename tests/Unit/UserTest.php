<?php

namespace Tests\Unit;

use App\Enums\SessionStatus;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_past_days_with_sessions_returns_correct_number_of_days(): void
    {
        $user = User::factory()->create();

        $result = $user->getPastDaysWithSessions(7);

        $this->assertCount(7, $result);
    }

    public function test_get_past_days_with_sessions_returns_correct_structure(): void
    {
        $user = User::factory()->create();

        $result = $user->getPastDaysWithSessions(7);

        foreach ($result as $day) {
            $this->assertArrayHasKey('date', $day);
            $this->assertArrayHasKey('hasSession', $day);
            $this->assertArrayHasKey('dayName', $day);
            $this->assertInstanceOf(\Carbon\Carbon::class, $day['date']);
            $this->assertIsBool($day['hasSession']);
            $this->assertIsString($day['dayName']);
        }
    }

    public function test_get_past_days_with_sessions_correctly_identifies_days_with_sessions(): void
    {
        $user = User::factory()->create();

        // Create a completed session for today
        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        // Create a completed session for 3 days ago
        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now()->subDays(3),
        ]);

        $result = $user->getPastDaysWithSessions(7);

        // Today (last item in array) should have a session
        $this->assertTrue($result[6]['hasSession']);

        // 3 days ago (index 3) should have a session
        $this->assertTrue($result[3]['hasSession']);

        // Yesterday (index 5) should not have a session
        $this->assertFalse($result[5]['hasSession']);
    }

    public function test_get_past_days_with_sessions_ignores_incomplete_sessions(): void
    {
        $user = User::factory()->create();

        // Create an incomplete session for today
        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::InProgress,
            'completed_at' => null,
        ]);

        $result = $user->getPastDaysWithSessions(7);

        // Today should not have a session since it's not completed
        $this->assertFalse($result[6]['hasSession']);
    }

    public function test_get_current_streak_returns_zero_with_no_sessions(): void
    {
        $user = User::factory()->create();

        $streak = $user->getCurrentStreak();

        $this->assertEquals(0, $streak);
    }

    public function test_get_current_streak_calculates_consecutive_days_correctly(): void
    {
        $user = User::factory()->create();

        // Create sessions for the last 5 consecutive days
        for ($i = 0; $i < 5; $i++) {
            Session::factory()->create([
                'user_id' => $user->id,
                'status' => SessionStatus::Completed,
                'completed_at' => now()->subDays($i),
            ]);
        }

        $streak = $user->getCurrentStreak();

        $this->assertEquals(5, $streak);
    }

    public function test_get_current_streak_stops_at_first_missing_day(): void
    {
        $user = User::factory()->create();

        // Create sessions for today and yesterday
        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now()->subDays(1),
        ]);

        // Skip day 2
        // Create a session for 3 days ago (this should not be counted)
        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now()->subDays(3),
        ]);

        $streak = $user->getCurrentStreak();

        $this->assertEquals(2, $streak);
    }

    public function test_get_current_streak_returns_zero_if_no_session_today(): void
    {
        $user = User::factory()->create();

        // Create sessions for yesterday and day before
        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now()->subDays(1),
        ]);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now()->subDays(2),
        ]);

        $streak = $user->getCurrentStreak();

        $this->assertEquals(0, $streak);
    }

    public function test_get_current_streak_ignores_other_users_sessions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create session for other user today
        Session::factory()->create([
            'user_id' => $otherUser->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now(),
        ]);

        $streak = $user->getCurrentStreak();

        $this->assertEquals(0, $streak);
    }
}
