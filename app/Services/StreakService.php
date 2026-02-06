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

        $activityDates = $this->getActivityDates($user, $lookbackDate);

        if ($activityDates->isEmpty()) {
            return 0;
        }

        while (true) {
            $dateString = $currentDate->format('Y-m-d');

            if (! $activityDates->contains($dateString)) {
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

        $activityDates = $this->getActivityDates($user, $lookbackDate);

        $todayString = $currentDate->format('Y-m-d');
        $hasPracticedToday = $activityDates->contains($todayString);

        if ($hasPracticedToday) {
            return $this->calculateStreak($user);
        }

        $streak = 1;
        $currentDate = $currentDate->copy()->subDay();

        while (true) {
            $dateString = $currentDate->format('Y-m-d');

            if (! $activityDates->contains($dateString)) {
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

    /**
     * Get unique activity dates from sessions and journal entries.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function getActivityDates(User $user, \Carbon\Carbon $lookbackDate): \Illuminate\Support\Collection
    {
        $timezone = $user->timezone ?? 'America/Los_Angeles';

        $sessionDates = $user->sessions()
            ->completed()
            ->where('completed_at', '>=', $lookbackDate->copy()->timezone('UTC'))
            ->get()
            ->map(function ($session) use ($timezone) {
                return TimezoneConverter::toUserTimezone(
                    $session->completed_at,
                    $timezone
                )->format('Y-m-d');
            })
            ->unique()
            ->values();

        $journalDates = $user->journalEntries()
            ->where('entry_date', '>=', $lookbackDate->toDateString())
            ->get()
            ->map(function ($entry) {
                return $entry->entry_date->toDateString();
            })
            ->unique()
            ->values();

        return $sessionDates->merge($journalDates)->unique()->values();
    }
}
