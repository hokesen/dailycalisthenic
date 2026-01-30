<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\SessionExercise;
use App\Models\User;
use App\Support\TimezoneConverter;

class ProgressionAnalyticsService
{
    public function __construct(
        private readonly StreakService $streakService
    ) {}

    public function getWeeklyProgressionSummary(User $user, int $days = 7): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $sessionExercises = SessionExercise::query()
            ->whereHas('session', function ($query) use ($user, $startDate, $endDate) {
                $query->where('user_id', $user->id)
                    ->completed()
                    ->whereBetween('completed_at', [$startDate, $endDate]);
            })
            ->with(['exercise.progression'])
            ->get();

        if ($sessionExercises->isEmpty()) {
            return [];
        }

        $sessionDataByExercise = $sessionExercises->groupBy('exercise_id');

        $workedExercises = $sessionExercises
            ->pluck('exercise')
            ->unique('id')
            ->filter(fn ($exercise) => $exercise->progression && $exercise->progression->progression_path_name);

        $progressionPaths = [];

        foreach ($workedExercises as $exercise) {
            $pathName = $exercise->progression->progression_path_name;

            if (isset($progressionPaths[$pathName])) {
                continue;
            }

            $exercisesInPath = [$exercise];
            $harderVariations = $exercise->getHarderVariations();
            $exercisesInPath = array_merge($exercisesInPath, $harderVariations);

            $exerciseData = [];
            foreach ($exercisesInPath as $ex) {
                $totalSeconds = $sessionDataByExercise->get($ex->id)?->sum('duration_seconds') ?? 0;

                $exerciseData[] = [
                    'name' => $ex->name,
                    'total_seconds' => $totalSeconds,
                ];
            }

            $progressionPaths[$pathName] = [
                'path_name' => $pathName,
                'exercises' => $exerciseData,
            ];
        }

        return array_values($progressionPaths);
    }

    public function getWeeklyStandaloneExercises(User $user, int $days = 7): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $sessionExercises = SessionExercise::query()
            ->whereHas('session', function ($query) use ($user, $startDate, $endDate) {
                $query->where('user_id', $user->id)
                    ->completed()
                    ->whereBetween('completed_at', [$startDate, $endDate]);
            })
            ->with(['exercise.progression'])
            ->get();

        if ($sessionExercises->isEmpty()) {
            return [];
        }

        $sessionDataByExercise = $sessionExercises->groupBy('exercise_id');

        $standaloneExercises = $sessionExercises
            ->pluck('exercise')
            ->unique('id')
            ->filter(fn ($exercise) => ! $exercise->progression || ! $exercise->progression->progression_path_name);

        $exerciseData = [];
        foreach ($standaloneExercises as $exercise) {
            $totalSeconds = $sessionDataByExercise->get($exercise->id)?->sum('duration_seconds') ?? 0;

            $exerciseData[] = [
                'name' => $exercise->name,
                'total_seconds' => $totalSeconds,
            ];
        }

        return $exerciseData;
    }

    public function getProgressionGanttData(User $user, int $days = 7): array
    {
        $userNow = $user->now();
        $startDate = $userNow->copy()->subDays($days - 1)->startOfDay();
        $endDate = $userNow->copy()->endOfDay();
        $timezone = $user->timezone ?? 'America/Los_Angeles';

        [$startDateUtc, $endDateUtc] = TimezoneConverter::convertDateRangeToUtc($startDate, $endDate, $timezone);

        $sessions = $user->sessions()
            ->whereNotNull('started_at')
            ->where(function ($query) use ($startDateUtc, $endDateUtc) {
                $query->whereBetween('started_at', [$startDateUtc, $endDateUtc])
                    ->orWhereBetween('completed_at', [$startDateUtc, $endDateUtc]);
            })
            ->with(['sessionExercises.exercise.progression'])
            ->get();

        $dailyData = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $userNow->copy()->subDays($i)->startOfDay();
            $dailyData[] = [
                'date' => $date,
                'dayName' => $date->format('D'),
                'exercises' => [],
            ];
        }

        foreach ($sessions as $session) {
            foreach ($session->sessionExercises as $sessionExercise) {
                if (! $sessionExercise->completed_at) {
                    continue;
                }

                $exerciseInTz = TimezoneConverter::fromTimestampToTimezone(
                    $sessionExercise->completed_at->getTimestamp(),
                    $timezone
                );

                foreach ($dailyData as $dayIndex => &$day) {
                    if ($exerciseInTz->toDateString() === $day['date']->toDateString()) {
                        $exerciseId = $sessionExercise->exercise_id;
                        if (! isset($day['exercises'][$exerciseId])) {
                            $day['exercises'][$exerciseId] = 0;
                        }
                        $day['exercises'][$exerciseId] += $sessionExercise->duration_seconds ?? 0;
                    }
                }
                unset($day);
            }
        }

        $allExercises = $sessions->flatMap(fn ($s) => $s->sessionExercises->filter(fn ($se) => $se->completed_at)->pluck('exercise'))
            ->unique('id')
            ->keyBy('id');

        $progressionPaths = [];
        $standaloneExercises = [];
        $processedProgressionPaths = [];
        $processedExerciseIds = [];

        foreach ($allExercises as $exercise) {
            $progression = $exercise->progression;

            if ($progression && $progression->progression_path_name) {
                $pathName = $progression->progression_path_name;

                if (isset($processedProgressionPaths[$pathName])) {
                    $processedExerciseIds[$exercise->id] = true;

                    continue;
                }

                $processedProgressionPaths[$pathName] = true;

                $pathExercises = Exercise::query()
                    ->whereHas('progression', function ($query) use ($pathName) {
                        $query->where('progression_path_name', $pathName);
                    })
                    ->with('progression')
                    ->get()
                    ->sortBy(fn ($ex) => $ex->progression->order ?? 0)
                    ->values();

                $exercisesInPath = [];
                foreach ($pathExercises as $index => $pathExercise) {
                    $exerciseId = $pathExercise->id;
                    $weeklyTotal = 0;
                    $dailySeconds = [];

                    foreach ($dailyData as $day) {
                        $seconds = $day['exercises'][$exerciseId] ?? 0;
                        $dailySeconds[] = $seconds;
                        $weeklyTotal += $seconds;
                    }

                    if ($weeklyTotal > 0) {
                        $exercisesInPath[] = [
                            'id' => $exerciseId,
                            'name' => $pathExercise->name,
                            'order' => $index,
                            'total_in_path' => count($pathExercises),
                            'weekly_seconds' => $weeklyTotal,
                            'daily_seconds' => $dailySeconds,
                            'streak' => $this->streakService->calculateExerciseStreak($user, $exerciseId),
                        ];
                    }
                    $processedExerciseIds[$exerciseId] = true;
                }

                if (! empty($exercisesInPath)) {
                    $progressionPaths[$pathName] = [
                        'path_name' => $pathName,
                        'exercises' => $exercisesInPath,
                    ];
                }
            } else {
                $exerciseId = $exercise->id;

                if (isset($processedExerciseIds[$exerciseId])) {
                    continue;
                }

                $weeklyTotal = 0;
                $dailySeconds = [];

                foreach ($dailyData as $day) {
                    $seconds = $day['exercises'][$exerciseId] ?? 0;
                    $dailySeconds[] = $seconds;
                    $weeklyTotal += $seconds;
                }

                if ($weeklyTotal > 0) {
                    $standaloneExercises[] = [
                        'id' => $exerciseId,
                        'name' => $exercise->name,
                        'weekly_seconds' => $weeklyTotal,
                        'daily_seconds' => $dailySeconds,
                        'streak' => $this->streakService->calculateExerciseStreak($user, $exerciseId),
                    ];
                }
            }
        }

        $dayLabels = [];
        $dayColumns = [];
        $dailyTotals = [];
        $weeklyTotal = 0;
        $todayIndex = null;

        foreach ($dailyData as $index => $day) {
            $dayLabels[] = substr($day['dayName'], 0, 1);
            $dayColumns[] = [
                'date' => $day['date']->format('M j'),
                'day_name' => $day['dayName'],
                'full_date' => $day['date']->format('Y-m-d'),
                'is_today' => $day['date']->isToday(),
            ];

            if ($day['date']->isToday()) {
                $todayIndex = $index;
            }

            $dayTotal = array_sum($day['exercises']);
            $dailyTotals[] = $dayTotal;
            $weeklyTotal += $dayTotal;
        }

        return [
            'progressions' => array_values($progressionPaths),
            'standalone' => $standaloneExercises,
            'dayLabels' => $dayLabels,
            'dayColumns' => $dayColumns,
            'dailyTotals' => $dailyTotals,
            'weeklyTotal' => $weeklyTotal,
            'date_range' => [
                'start' => $startDate->format('M j'),
                'end' => $endDate->format('M j'),
            ],
            'today_index' => $todayIndex,
        ];
    }
}
