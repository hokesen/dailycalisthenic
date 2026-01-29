<?php

namespace Tests\Feature\Services;

use App\Enums\SessionStatus;
use App\Models\Session;
use App\Models\User;
use App\Services\CachedStreakService;
use App\Services\StreakService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CachedStreakServiceTest extends TestCase
{
    use RefreshDatabase;

    private CachedStreakService $cachedService;

    private StreakService $streakService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->streakService = new StreakService;
        $this->cachedService = new CachedStreakService($this->streakService);
        Cache::flush();
    }

    public function test_calculate_streak_caches_result(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        $streak = $this->cachedService->calculateStreak($user);

        $this->assertEquals(1, $streak);
        $this->assertTrue(Cache::has("user:{$user->id}:streak"));
    }

    public function test_calculate_streak_returns_cached_value(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        $firstCall = $this->cachedService->calculateStreak($user);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC')->subDay(),
            'total_duration_seconds' => 300,
        ]);

        $secondCall = $this->cachedService->calculateStreak($user);

        $this->assertEquals($firstCall, $secondCall);
        $this->assertEquals(1, $secondCall);
    }

    public function test_calculate_potential_streak_caches_result(): void
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

        $potentialStreak = $this->cachedService->calculatePotentialStreak($user);

        $this->assertEquals(4, $potentialStreak);
        $this->assertTrue(Cache::has("user:{$user->id}:potential_streak"));
    }

    public function test_calculate_exercise_streak_caches_result(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $exerciseId = 1;

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        $streak = $this->cachedService->calculateExerciseStreak($user, $exerciseId);

        $this->assertIsInt($streak);
        $this->assertTrue(Cache::has("user:{$user->id}:exercise_streak:{$exerciseId}"));
    }

    public function test_invalidate_user_cache_clears_streak_caches(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        $this->cachedService->calculateStreak($user);
        $this->cachedService->calculatePotentialStreak($user);

        $this->assertTrue(Cache::has("user:{$user->id}:streak"));
        $this->assertTrue(Cache::has("user:{$user->id}:potential_streak"));

        $this->cachedService->invalidateUserCache($user->id);

        $this->assertFalse(Cache::has("user:{$user->id}:streak"));
        $this->assertFalse(Cache::has("user:{$user->id}:potential_streak"));
    }

    public function test_cache_invalidation_allows_fresh_calculation(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        $firstStreak = $this->cachedService->calculateStreak($user);
        $this->assertEquals(1, $firstStreak);

        $this->cachedService->invalidateUserCache($user->id);

        Session::factory()->create([
            'user_id' => $user->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC')->subDay(),
            'total_duration_seconds' => 300,
        ]);

        $secondStreak = $this->cachedService->calculateStreak($user);
        $this->assertEquals(2, $secondStreak);
    }
}
