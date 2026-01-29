<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CachedProgressionAnalyticsService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const CACHE_PREFIX = 'user:';

    public function __construct(
        private ProgressionAnalyticsService $analyticsService
    ) {}

    /**
     * Get progression gantt data with caching
     */
    public function getProgressionGanttData(User $user, int $days = 7): array
    {
        $cacheKey = $this->getCacheKey($user->id, "gantt:{$days}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $days) {
            return $this->analyticsService->getProgressionGanttData($user, $days);
        });
    }

    /**
     * Get weekly progression summary with caching
     */
    public function getWeeklyProgressionSummary(User $user, int $days = 7): array
    {
        $cacheKey = $this->getCacheKey($user->id, "progression_summary:{$days}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $days) {
            return $this->analyticsService->getWeeklyProgressionSummary($user, $days);
        });
    }

    /**
     * Get weekly standalone exercises with caching
     */
    public function getWeeklyStandaloneExercises(User $user, int $days = 7): array
    {
        $cacheKey = $this->getCacheKey($user->id, "standalone:{$days}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $days) {
            return $this->analyticsService->getWeeklyStandaloneExercises($user, $days);
        });
    }

    /**
     * Invalidate all analytics caches for a user
     */
    public function invalidateUserCache(int $userId): void
    {
        // Clear common cache keys
        $days = [7, 14, 30];
        foreach ($days as $day) {
            Cache::forget($this->getCacheKey($userId, "gantt:{$day}"));
            Cache::forget($this->getCacheKey($userId, "progression_summary:{$day}"));
            Cache::forget($this->getCacheKey($userId, "standalone:{$day}"));
        }
    }

    /**
     * Generate cache key for a user's analytics data
     */
    private function getCacheKey(int $userId, string $type): string
    {
        return self::CACHE_PREFIX."{$userId}:analytics:{$type}";
    }
}
