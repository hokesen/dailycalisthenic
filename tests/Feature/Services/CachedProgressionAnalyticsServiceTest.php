<?php

namespace Tests\Feature\Services;

use App\Enums\SessionStatus;
use App\Models\Exercise;
use App\Models\Session;
use App\Models\SessionExercise;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Services\CachedProgressionAnalyticsService;
use App\Services\ProgressionAnalyticsService;
use App\Services\StreakService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CachedProgressionAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private CachedProgressionAnalyticsService $cachedService;

    private ProgressionAnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $streakService = new StreakService;
        $this->analyticsService = new ProgressionAnalyticsService($streakService);
        $this->cachedService = new CachedProgressionAnalyticsService($this->analyticsService);
        Cache::flush();
    }

    public function test_get_progression_gantt_data_caches_result(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $exercise = Exercise::factory()->create();

        $session = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'duration_seconds' => 120,
        ]);

        $result = $this->cachedService->getProgressionGanttData($user, 7);

        $this->assertIsArray($result);
        $this->assertTrue(Cache::has("user:{$user->id}:analytics:gantt:7"));
    }

    public function test_get_progression_gantt_data_returns_cached_value(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        $firstCall = $this->cachedService->getProgressionGanttData($user, 7);

        // Create new session after first call
        $template = SessionTemplate::factory()->create(['user_id' => $user->id]);
        $exercise = Exercise::factory()->create();
        $session = Session::factory()->create([
            'user_id' => $user->id,
            'session_template_id' => $template->id,
            'status' => SessionStatus::Completed,
            'completed_at' => $user->now()->timezone('UTC'),
            'total_duration_seconds' => 300,
        ]);

        SessionExercise::factory()->create([
            'session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'duration_seconds' => 120,
        ]);

        $secondCall = $this->cachedService->getProgressionGanttData($user, 7);

        // Should return cached result, so weekly total should be the same
        $this->assertEquals($firstCall['weeklyTotal'], $secondCall['weeklyTotal']);
    }

    public function test_get_weekly_progression_summary_caches_result(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        $result = $this->cachedService->getWeeklyProgressionSummary($user, 7);

        $this->assertIsArray($result);
        $this->assertTrue(Cache::has("user:{$user->id}:analytics:progression_summary:7"));
    }

    public function test_get_weekly_standalone_exercises_caches_result(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        $result = $this->cachedService->getWeeklyStandaloneExercises($user, 7);

        $this->assertIsArray($result);
        $this->assertTrue(Cache::has("user:{$user->id}:analytics:standalone:7"));
    }

    public function test_invalidate_user_cache_clears_analytics_caches(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        $this->cachedService->getProgressionGanttData($user, 7);
        $this->cachedService->getWeeklyProgressionSummary($user, 7);
        $this->cachedService->getWeeklyStandaloneExercises($user, 7);

        $this->assertTrue(Cache::has("user:{$user->id}:analytics:gantt:7"));
        $this->assertTrue(Cache::has("user:{$user->id}:analytics:progression_summary:7"));
        $this->assertTrue(Cache::has("user:{$user->id}:analytics:standalone:7"));

        $this->cachedService->invalidateUserCache($user->id);

        $this->assertFalse(Cache::has("user:{$user->id}:analytics:gantt:7"));
        $this->assertFalse(Cache::has("user:{$user->id}:analytics:progression_summary:7"));
        $this->assertFalse(Cache::has("user:{$user->id}:analytics:standalone:7"));
    }

    public function test_different_day_counts_produce_different_cache_keys(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);

        $this->cachedService->getProgressionGanttData($user, 7);
        $this->cachedService->getProgressionGanttData($user, 14);

        $this->assertTrue(Cache::has("user:{$user->id}:analytics:gantt:7"));
        $this->assertTrue(Cache::has("user:{$user->id}:analytics:gantt:14"));

        $this->cachedService->invalidateUserCache($user->id);

        $this->assertFalse(Cache::has("user:{$user->id}:analytics:gantt:7"));
        $this->assertFalse(Cache::has("user:{$user->id}:analytics:gantt:14"));
    }
}
