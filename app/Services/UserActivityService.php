<?php

namespace App\Services;

use App\Models\User;
use App\Support\TimezoneConverter;

class UserActivityService
{
    public function hasPracticedToday(User $user): bool
    {
        $today = $user->now()->startOfDay();
        $timezone = $user->timezone ?? 'America/Los_Angeles';

        $sessions = $user->sessions()
            ->whereIn('status', ['completed', 'in_progress'])
            ->where('total_duration_seconds', '>', 0)
            ->where(function ($query) use ($today, $timezone) {
                $startOfDayUtc = $today->copy()->timezone($timezone)->startOfDay()->timezone('UTC');
                $endOfDayUtc = $today->copy()->timezone($timezone)->endOfDay()->timezone('UTC');

                $query->whereBetween('completed_at', [$startOfDayUtc, $endOfDayUtc])
                    ->orWhereBetween('started_at', [$startOfDayUtc, $endOfDayUtc]);
            })
            ->get();

        return $sessions->isNotEmpty();
    }

    public function getPastDaysWithActivity(User $user, int $days = 7): array
    {
        $result = [];
        $today = $user->now()->startOfDay();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $timezone = $user->timezone ?? 'America/Los_Angeles';
            $startOfDayUtc = $date->copy()->timezone($timezone)->startOfDay()->timezone('UTC');
            $endOfDayUtc = $date->copy()->timezone($timezone)->endOfDay()->timezone('UTC');

            $hasSession = $user->sessions()
                ->whereIn('status', ['completed', 'in_progress'])
                ->where('total_duration_seconds', '>', 0)
                ->where(function ($query) use ($startOfDayUtc, $endOfDayUtc) {
                    $query->whereBetween('completed_at', [$startOfDayUtc, $endOfDayUtc])
                        ->orWhereBetween('started_at', [$startOfDayUtc, $endOfDayUtc]);
                })
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
            ->whereIn('status', ['completed', 'in_progress'])
            ->where('total_duration_seconds', '>', 0)
            ->where(function ($query) use ($startDateUtc, $endDateUtc) {
                $query->whereBetween('completed_at', [$startDateUtc, $endDateUtc])
                    ->orWhereBetween('started_at', [$startDateUtc, $endDateUtc]);
            })
            ->with(['sessionExercises.exercise'])
            ->get();

        $breakdown = [];
        $timezone = $user->timezone ?? 'America/Los_Angeles';

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $userNow->copy()->subDays($i)->startOfDay();

            $daySessions = $sessions->filter(function ($session) use ($date, $timezone) {
                $activityAt = $session->completed_at ?? $session->started_at ?? $session->updated_at;
                $sessionInTz = TimezoneConverter::fromTimestampToTimezone(
                    $activityAt->getTimestamp(),
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
