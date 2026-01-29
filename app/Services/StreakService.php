<?php

namespace App\Services;

use App\Models\User;
use App\Support\TimezoneConverter;

class StreakService
{
    /**
     * Calculate the current streak for a user using optimized single query
     */
    public function calculateStreak(User $user): int
    {
        $streak = 0;
        $currentDate = $user->now()->startOfDay();

        // Fetch sessions from last 400 days to account for timezone conversions
        $lookbackDate = $currentDate->copy()->subDays(400);

        $sessionDates = $user->sessions()
            ->completed()
            ->where('completed_at', '>=', $lookbackDate->copy()->timezone('UTC'))
            ->get()
            ->map(function ($session) use ($user) {
                return TimezoneConverter::toUserTimezone(
                    $session->completed_at,
                    $user->timezone ?? 'America/Los_Angeles'
                )->format('Y-m-d');
            })
            ->unique()
            ->values();

        if ($sessionDates->isEmpty()) {
            return 0;
        }

        while (true) {
            $dateString = $currentDate->format('Y-m-d');

            if (! $sessionDates->contains($dateString)) {
                break;
            }

            $streak++;
            $currentDate = $currentDate->copy()->subDay();
        }

        return $streak;
    }

    /**
     * Calculate the potential streak if user practices today
     */
    public function calculatePotentialStreak(User $user): int
    {
        $currentDate = $user->now()->startOfDay();

        // Fetch sessions from last 400 days to account for timezone conversions
        $lookbackDate = $currentDate->copy()->subDays(400);

        $sessionDates = $user->sessions()
            ->completed()
            ->where('completed_at', '>=', $lookbackDate->copy()->timezone('UTC'))
            ->get()
            ->map(function ($session) use ($user) {
                return TimezoneConverter::toUserTimezone(
                    $session->completed_at,
                    $user->timezone ?? 'America/Los_Angeles'
                )->format('Y-m-d');
            })
            ->unique()
            ->values();

        $todayString = $currentDate->format('Y-m-d');
        $hasPracticedToday = $sessionDates->contains($todayString);

        if ($hasPracticedToday) {
            return $this->calculateStreak($user);
        }

        $streak = 1;
        $currentDate = $currentDate->copy()->subDay();

        while (true) {
            $dateString = $currentDate->format('Y-m-d');

            if (! $sessionDates->contains($dateString)) {
                break;
            }

            $streak++;
            $currentDate = $currentDate->copy()->subDay();
        }

        return $streak;
    }

    /**
     * Calculate streak for a specific exercise using optimized single query
     */
    public function calculateExerciseStreak(User $user, int $exerciseId): int
    {
        $streak = 0;
        $currentDate = $user->now()->startOfDay();

        // Fetch sessions from last 400 days to account for timezone conversions
        $lookbackDate = $currentDate->copy()->subDays(400);

        $sessionDates = $user->sessions()
            ->completed()
            ->where('completed_at', '>=', $lookbackDate->copy()->timezone('UTC'))
            ->whereHas('sessionExercises', function ($query) use ($exerciseId) {
                $query->where('exercise_id', $exerciseId);
            })
            ->get()
            ->map(function ($session) use ($user) {
                return TimezoneConverter::toUserTimezone(
                    $session->completed_at,
                    $user->timezone ?? 'America/Los_Angeles'
                )->format('Y-m-d');
            })
            ->unique()
            ->values();

        if ($sessionDates->isEmpty()) {
            return 0;
        }

        while (true) {
            $dateString = $currentDate->format('Y-m-d');

            if (! $sessionDates->contains($dateString)) {
                break;
            }

            $streak++;
            $currentDate = $currentDate->copy()->subDay();
        }

        return $streak;
    }
}
