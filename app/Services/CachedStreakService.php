<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CachedStreakService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const CACHE_PREFIX = 'user:';

    public function __construct(
        private StreakService $streakService
    ) {}

    /**
     * Calculate the current streak for a user (with caching)
     */
    public function calculateStreak(User $user): int
    {
        $cacheKey = $this->getCacheKey($user->id, 'streak');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return $this->streakService->calculateStreak($user);
        });
    }

    /**
     * Calculate the potential streak if user practices today (with caching)
     */
    public function calculatePotentialStreak(User $user): int
    {
        $cacheKey = $this->getCacheKey($user->id, 'potential_streak');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return $this->streakService->calculatePotentialStreak($user);
        });
    }

    /**
     * Calculate streak for a specific exercise (with caching)
     */
    public function calculateExerciseStreak(User $user, int $exerciseId): int
    {
        $cacheKey = $this->getCacheKey($user->id, "exercise_streak:{$exerciseId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $exerciseId) {
            return $this->streakService->calculateExerciseStreak($user, $exerciseId);
        });
    }

    /**
     * Invalidate all streak caches for a user
     */
    public function invalidateUserCache(int $userId): void
    {
        // Clear main streak cache
        Cache::forget($this->getCacheKey($userId, 'streak'));
        Cache::forget($this->getCacheKey($userId, 'potential_streak'));

        // Note: We don't clear all exercise streak caches here as we'd need to track which exercises
        // have been cached. In practice, the 1-hour TTL is acceptable for exercise streaks.
    }

    /**
     * Generate cache key for a user's streak data
     */
    private function getCacheKey(int $userId, string $type): string
    {
        return self::CACHE_PREFIX."{$userId}:{$type}";
    }
}
