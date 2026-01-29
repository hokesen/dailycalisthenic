<?php

namespace Tests\Feature\Services;

use App\Enums\SessionStatus;
use App\Models\Session;
use App\Models\User;
use App\Services\StreakService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StreakServiceTest extends TestCase
{
    use RefreshDatabase;

    private StreakService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StreakService;
    }

    public function test_calculate_streak_returns_zero_when_no_sessions(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        $streak = $this->service->calculateStreak($user);

        $this->assertEquals(0, $streak);
    }

    public function test_calculate_streak_returns_zero_when_no_session_today(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC')->subDays(2),
            'total_duration_seconds' => 300,
        ]);

        $streak = $this->service->calculateStreak($user);

        $this->assertEquals(0, $streak);
    }

    public function test_calculate_streak_returns_one_when_only_today(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        $streak = $this->service->calculateStreak($user);

        $this->assertEquals(1, $streak);
    }

    public function test_calculate_streak_counts_consecutive_days(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        for ($i = 0; $i < 5; $i++) {
            Session::factory()->create([
                'user_id' => $user->id,
                'status' => SessionStatus::Completed,
                'completed_at' => $user->now()->timezone('UTC')->subDays($i)->timezone('UTC'),
                'total_duration_seconds' => 300,
            ]);
        }

        $streak = $this->service->calculateStreak($user);

        $this->assertEquals(5, $streak);
    }

    public function test_calculate_streak_stops_at_gap(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC')->subDays(1),
            'total_duration_seconds' => 300,
        ]);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC')->subDays(5),
            'total_duration_seconds' => 300,
        ]);

        $streak = $this->service->calculateStreak($user);

        $this->assertEquals(2, $streak);
    }

    public function test_calculate_potential_streak_returns_one_when_no_sessions(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        $potentialStreak = $this->service->calculatePotentialStreak($user);

        $this->assertEquals(1, $potentialStreak);
    }

    public function test_calculate_potential_streak_adds_one_to_yesterdays_streak(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        for ($i = 1; $i <= 3; $i++) {
            Session::factory()->create([
                'user_id' => $user->id,
                'status' => SessionStatus::Completed,
                'completed_at' => $user->now()->timezone('UTC')->subDays($i),
                'total_duration_seconds' => 300,
            ]);
        }

        $potentialStreak = $this->service->calculatePotentialStreak($user);

        $this->assertEquals(4, $potentialStreak);
    }

    public function test_calculate_potential_streak_returns_current_streak_plus_one_when_practiced_today(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC')->subDay(),
            'total_duration_seconds' => 300,
        ]);

        $potentialStreak = $this->service->calculatePotentialStreak($user);
        $currentStreak = $this->service->calculateStreak($user);

        $this->assertEquals($currentStreak, $potentialStreak);
    }
}
