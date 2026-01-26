<?php

namespace App\Services;

use App\Models\User;

class StreakService
{
    public function calculateStreak(User $user): int
    {
        $streak = 0;
        $currentDate = $user->now()->startOfDay();

        while (true) {
            $hasSession = $user->sessions()
                ->completed()
                ->onDate($currentDate, $user->timezone ?? 'America/Los_Angeles')
                ->exists();

            if (! $hasSession) {
                break;
            }

            $streak++;
            $currentDate = $currentDate->copy()->subDay();
        }

        return $streak;
    }

    public function calculatePotentialStreak(User $user): int
    {
        $hasPracticedToday = $user->sessions()
            ->completed()
            ->onDate($user->now()->startOfDay(), $user->timezone ?? 'America/Los_Angeles')
            ->exists();

        if ($hasPracticedToday) {
            return $this->calculateStreak($user);
        }

        $streak = 1;
        $currentDate = $user->now()->startOfDay()->subDay();

        while (true) {
            $hasSession = $user->sessions()
                ->completed()
                ->onDate($currentDate, $user->timezone ?? 'America/Los_Angeles')
                ->exists();

            if (! $hasSession) {
                break;
            }

            $streak++;
            $currentDate = $currentDate->copy()->subDay();
        }

        return $streak;
    }

    public function calculateExerciseStreak(User $user, int $exerciseId): int
    {
        $streak = 0;
        $currentDate = $user->now()->startOfDay();

        while (true) {
            $hasExercise = $user->sessions()
                ->completed()
                ->onDate($currentDate, $user->timezone ?? 'America/Los_Angeles')
                ->whereHas('sessionExercises', function ($query) use ($exerciseId) {
                    $query->where('exercise_id', $exerciseId);
                })
                ->exists();

            if (! $hasExercise) {
                break;
            }

            $streak++;
            $currentDate = $currentDate->copy()->subDay();
        }

        return $streak;
    }
}
