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
            ->where(function ($query) {
                $query->where('total_duration_seconds', '>', 0)
                    ->orWhereHas('sessionExercises', function ($exerciseQuery) {
                        $exerciseQuery->where('duration_seconds', '>', 0);
                    });
            })
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
                ->where(function ($query) {
                    $query->where('total_duration_seconds', '>', 0)
                        ->orWhereHas('sessionExercises', function ($exerciseQuery) {
                            $exerciseQuery->where('duration_seconds', '>', 0);
                        });
                })
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
            ->where(function ($query) {
                $query->where('total_duration_seconds', '>', 0)
                    ->orWhereHas('sessionExercises', function ($exerciseQuery) {
                        $exerciseQuery->where('duration_seconds', '>', 0);
                    });
            })
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

    /**
     * Get a lightweight recent history snapshot for quick dashboard scanning.
     *
     * @return array{
     *     days: array<int, array{
     *         date: \Carbon\Carbon,
     *         isoDate: string,
     *         dayName: string,
     *         dayOfMonth: string,
     *         isToday: bool,
     *         hasActivity: bool,
     *         sessionCount: int,
     *         journalCount: int,
     *         totalSeconds: int,
     *         topExercises: array<int, string>
     *     }>,
     *     totals: array{
     *         activeDays: int,
     *         sessionCount: int,
     *         journalCount: int,
     *         totalSeconds: int
     *     },
     *     maxDaySeconds: int
     * }
     */
    public function getRecentHistorySnapshot(User $user, int $days = 14): array
    {
        $days = max(1, $days);

        $userNow = $user->now();
        $timezone = $user->timezone ?? 'America/Los_Angeles';
        $startDate = $userNow->copy()->subDays($days - 1)->startOfDay();
        $endDate = $userNow->copy()->endOfDay();

        [$startDateUtc, $endDateUtc] = TimezoneConverter::convertDateRangeToUtc($startDate, $endDate, $timezone);

        $sessions = $user->sessions()
            ->whereIn('status', ['completed', 'in_progress'])
            ->where(function ($query) {
                $query->where('total_duration_seconds', '>', 0)
                    ->orWhereHas('sessionExercises', function ($exerciseQuery) {
                        $exerciseQuery->where('duration_seconds', '>', 0);
                    });
            })
            ->where(function ($query) use ($startDateUtc, $endDateUtc) {
                $query->whereBetween('completed_at', [$startDateUtc, $endDateUtc])
                    ->orWhereBetween('started_at', [$startDateUtc, $endDateUtc]);
            })
            ->with(['sessionExercises.exercise'])
            ->get();

        $journals = $user->journalEntries()
            ->whereBetween('entry_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with('journalExercises')
            ->get();

        $dayRows = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $userNow->copy()->subDays($i)->startOfDay();
            $isoDate = $date->toDateString();

            $daySessions = $sessions->filter(function ($session) use ($isoDate, $timezone) {
                $activityAt = $session->completed_at ?? $session->started_at ?? $session->updated_at;
                $sessionInTz = TimezoneConverter::fromTimestampToTimezone(
                    $activityAt->getTimestamp(),
                    $timezone
                );

                return $sessionInTz->toDateString() === $isoDate;
            });

            $dayJournals = $journals->filter(fn ($entry) => $entry->entry_date->toDateString() === $isoDate);

            $exerciseTotals = [];
            foreach ($daySessions as $session) {
                foreach ($session->sessionExercises as $sessionExercise) {
                    if (! $sessionExercise->exercise) {
                        continue;
                    }

                    $exerciseName = $sessionExercise->exercise->name;
                    $exerciseTotals[$exerciseName] = ($exerciseTotals[$exerciseName] ?? 0) + (int) ($sessionExercise->duration_seconds ?? 0);
                }
            }

            arsort($exerciseTotals);

            $sessionSeconds = (int) $daySessions->sum(function ($session) {
                $sessionTotal = (int) ($session->total_duration_seconds ?? 0);
                if ($sessionTotal > 0) {
                    return $sessionTotal;
                }

                return (int) $session->sessionExercises->sum(fn ($exercise) => (int) ($exercise->duration_seconds ?? 0));
            });
            $journalSeconds = (int) $dayJournals
                ->flatMap(fn ($entry) => $entry->journalExercises)
                ->sum(fn ($exercise) => ((int) ($exercise->duration_minutes ?? 0)) * 60);

            $dayRows[] = [
                'date' => $date,
                'isoDate' => $isoDate,
                'dayName' => $date->format('D'),
                'dayOfMonth' => $date->format('j'),
                'isToday' => $date->isSameDay($userNow),
                'hasActivity' => $daySessions->isNotEmpty() || $dayJournals->isNotEmpty(),
                'sessionCount' => $daySessions->count(),
                'journalCount' => $dayJournals->count(),
                'totalSeconds' => $sessionSeconds + $journalSeconds,
                'topExercises' => array_slice(array_keys($exerciseTotals), 0, 2),
            ];
        }

        $activeDays = collect($dayRows)->where('hasActivity', true)->count();
        $sessionCount = (int) collect($dayRows)->sum('sessionCount');
        $journalCount = (int) collect($dayRows)->sum('journalCount');
        $totalSeconds = (int) collect($dayRows)->sum('totalSeconds');
        $maxDaySeconds = max(1, (int) collect($dayRows)->max('totalSeconds'));

        return [
            'days' => $dayRows,
            'totals' => [
                'activeDays' => $activeDays,
                'sessionCount' => $sessionCount,
                'journalCount' => $journalCount,
                'totalSeconds' => $totalSeconds,
            ],
            'maxDaySeconds' => $maxDaySeconds,
        ];
    }
}
