<?php

namespace App\Services;

use App\Models\User;
use App\Support\TimezoneConverter;

class UserActivityService
{
    public function hasPracticedToday(User $user): bool
    {
        $today = $user->now()->startOfDay();

        return $user->sessions()
            ->completed()
            ->onDate($today, $user->timezone ?? 'America/Los_Angeles')
            ->exists();
    }

    public function getPastDaysWithActivity(User $user, int $days = 7): array
    {
        $result = [];
        $today = $user->now()->startOfDay();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $hasSession = $user->sessions()
                ->completed()
                ->onDate($date, $user->timezone ?? 'America/Los_Angeles')
                ->exists();

            $result[] = [
                'date' => $date,
                'hasSession' => $hasSession,
                'dayName' => $date->format('D'),
            ];
        }

        return $result;
    }

    public function getWeeklyExerciseBreakdown(User $user, int $days = 7): array
    {
        $userNow = $user->now();
        $startDate = $userNow->copy()->subDays($days - 1)->startOfDay();
        $endDate = $userNow->copy()->endOfDay();

        [$startDateUtc, $endDateUtc] = TimezoneConverter::convertDateRangeToUtc($startDate, $endDate, $user->timezone ?? 'America/Los_Angeles');

        $sessions = $user->sessions()
            ->completed()
            ->whereBetween('completed_at', [$startDateUtc, $endDateUtc])
            ->with(['sessionExercises.exercise'])
            ->get();

        $breakdown = [];
        $timezone = $user->timezone ?? 'America/Los_Angeles';

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $userNow->copy()->subDays($i)->startOfDay();

            $daySessions = $sessions->filter(function ($session) use ($date, $timezone) {
                $sessionInTz = TimezoneConverter::fromTimestampToTimezone(
                    $session->completed_at->getTimestamp(),
                    $timezone
                );

                return $sessionInTz->toDateString() === $date->toDateString();
            });

            $exercises = [];
            foreach ($daySessions as $session) {
                foreach ($session->sessionExercises as $sessionExercise) {
                    $exerciseId = $sessionExercise->exercise_id;
                    if (! isset($exercises[$exerciseId])) {
                        $exercises[$exerciseId] = [
                            'name' => $sessionExercise->exercise->name,
                            'total_seconds' => 0,
                        ];
                    }
                    $exercises[$exerciseId]['total_seconds'] += $sessionExercise->duration_seconds ?? 0;
                }
            }

            $breakdown[] = [
                'date' => $date,
                'dayName' => $date->format('D'),
                'hasSession' => $daySessions->isNotEmpty(),
                'exercises' => array_values($exercises),
            ];
        }

        return $breakdown;
    }
}
