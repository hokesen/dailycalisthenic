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

    public function test_get_potential_streak_returns_one_with_no_sessions(): void
    {
        $user = User::factory()->create();

        $potentialStreak = $user->getPotentialStreak();

        $this->assertEquals(1, $potentialStreak);
    }

    public function test_get_potential_streak_counts_yesterday_streak_plus_today(): void
    {
        $user = User::factory()->create();

        // Create sessions for yesterday and 2 days ago (but not today)
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

        $potentialStreak = $user->getPotentialStreak();

        // Yesterday streak was 2, so potential streak if completed today = 3
        $this->assertEquals(3, $potentialStreak);
    }

    public function test_get_potential_streak_returns_one_if_gap_yesterday(): void
    {
        $user = User::factory()->create();

        // Create session for 2 days ago but NOT yesterday
        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => now()->subDays(2),
        ]);

        $potentialStreak = $user->getPotentialStreak();

        // No session yesterday, so potential streak = 1 (just today)
        $this->assertEquals(1, $potentialStreak);
    }

    public function test_sessions_are_grouped_by_user_timezone(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']); // PST/PDT (UTC-8/-7)

        // Create a session completed at 11:30 PM PST on Jan 1 (which is 7:30 AM UTC on Jan 2)
        $pstDate = \Carbon\Carbon::create(2026, 1, 1, 23, 30, 0, 'America/Los_Angeles');
        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $pstDate->copy()->timezone('UTC'), // Store as UTC
        ]);

        // Check if the session shows up on Jan 1 in PST (not Jan 2)
        $jan1Pst = \Carbon\Carbon::create(2026, 1, 1, 0, 0, 0, 'America/Los_Angeles');
        $hasSessionOnJan1 = $user->sessions()
            ->completed()
            ->onDate($jan1Pst, 'America/Los_Angeles')
            ->exists();

        $this->assertTrue($hasSessionOnJan1, 'Session should appear on Jan 1 in PST timezone');

        // Verify it does NOT show up on Jan 2 in PST
        $jan2Pst = \Carbon\Carbon::create(2026, 1, 2, 0, 0, 0, 'America/Los_Angeles');
        $hasSessionOnJan2 = $user->sessions()
            ->completed()
            ->onDate($jan2Pst, 'America/Los_Angeles')
            ->exists();

        $this->assertFalse($hasSessionOnJan2, 'Session should NOT appear on Jan 2 in PST timezone');
    }

    public function test_streak_calculation_uses_user_timezone(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        // Set "now" to Jan 3 12:00 PST (which is Jan 3 20:00 UTC)
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 1, 3, 20, 0, 0, 'UTC'));

        // Create sessions for 3 consecutive days in PST
        // Jan 1 at 10:00 PST = Jan 1 18:00 UTC
        // Jan 2 at 10:00 PST = Jan 2 18:00 UTC
        // Jan 3 at 10:00 PST = Jan 3 18:00 UTC
        for ($i = 0; $i < 3; $i++) {
            $pstDate = \Carbon\Carbon::create(2026, 1, 1 + $i, 10, 0, 0, 'America/Los_Angeles');
            Session::factory()->create([
                'user_id' => $user->id,
                'status' => SessionStatus::Completed,
                'completed_at' => $pstDate->copy()->timezone('UTC'),
            ]);
        }

        $streak = $user->getCurrentStreak();

        $this->assertEquals(3, $streak, 'Streak should be 3 days when counted in PST timezone');

        \Carbon\Carbon::setTestNow(); // Reset
    }

    public function test_weekly_breakdown_uses_user_timezone(): void
    {
        // Set test time FIRST to Jan 7 in UTC to avoid timezone issues with database inserts
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 1, 7, 20, 0, 0, 'UTC')); // This is 12:00 PM PST

        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        // Create a session at 11:30 PM PST on Jan 1 (which is 7:30 AM UTC on Jan 2)
        $utcCompletedAt = \Carbon\Carbon::create(2026, 1, 2, 7, 30, 0, 'UTC');
        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $utcCompletedAt,
        ]);

        $breakdown = $user->getWeeklyExerciseBreakdown(7);

        // The session should appear on Jan 1 (index 0), not Jan 2 (index 1)
        $this->assertTrue($breakdown[0]['hasSession'], 'Session should appear on Jan 1 in PST');
        $this->assertFalse($breakdown[1]['hasSession'], 'Session should NOT appear on Jan 2 in PST');

        \Carbon\Carbon::setTestNow(); // Reset
    }
}
