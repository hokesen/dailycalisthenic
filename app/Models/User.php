<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    /**
     * Get current time in user's timezone.
     */
    public function now(): \Carbon\Carbon
    {
        return now()->timezone($this->timezone ?? 'America/Los_Angeles');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function sessionTemplates(): HasMany
    {
        return $this->hasMany(SessionTemplate::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(UserGoal::class);
    }

    public function activeGoal(): HasOne
    {
        return $this->hasOne(UserGoal::class)
            ->where('is_active', true)
            ->latestOfMany();
    }

    public function exerciseProgress(): HasMany
    {
        return $this->hasMany(UserExerciseProgress::class);
    }

    public function currentExercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'user_exercise_progress')
            ->wherePivot('status', 'current')
            ->withPivot(['best_sets', 'best_reps', 'best_duration_seconds', 'started_at'])
            ->withTimestamps();
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
    }

    /**
     * Get the past N days with session completion status.
     *
     * @return array<int, array{date: \Carbon\Carbon, hasSession: bool, dayName: string}>
     */
    public function getPastDaysWithSessions(int $days = 7): array
    {
        $result = [];
        $today = $this->now()->startOfDay();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $hasSession = $this->sessions()
                ->completed()
                ->onDate($date, $this->timezone ?? 'America/Los_Angeles')
                ->exists();

            $result[] = [
                'date' => $date,
                'hasSession' => $hasSession,
                'dayName' => $date->format('D'),
            ];
        }

        return $result;
    }

    /**
     * Calculate the current streak of consecutive days with completed sessions.
     */
    public function getCurrentStreak(): int
    {
        $streak = 0;
        $currentDate = $this->now()->startOfDay();

        while (true) {
            $hasSession = $this->sessions()
                ->completed()
                ->onDate($currentDate, $this->timezone ?? 'America/Los_Angeles')
                ->exists();

            if (! $hasSession) {
                break;
            }

            $streak++;
            $currentDate = $currentDate->copy()->subDay();
        }

        return $streak;
    }

    /**
     * Get weekly exercise breakdown with minutes per exercise for each day.
     *
     * @return array<int, array{date: \Carbon\Carbon, dayName: string, hasSession: bool, exercises: array}>
     */
    public function getWeeklyExerciseBreakdown(int $days = 7): array
    {
        $userNow = $this->now();
        $startDate = $userNow->copy()->subDays($days - 1)->startOfDay();
        $endDate = $userNow->copy()->endOfDay();

        // Convert to UTC for database query
        $startDateUtc = $startDate->copy()->timezone('UTC');
        $endDateUtc = $endDate->copy()->timezone('UTC');

        // Single query to fetch all completed sessions with exercises
        $sessions = $this->sessions()
            ->completed()
            ->whereBetween('completed_at', [$startDateUtc, $endDateUtc])
            ->with(['sessionExercises.exercise'])
            ->get();

        // Build daily breakdown
        $breakdown = [];
        $timezone = $this->timezone ?? 'America/Los_Angeles';

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $userNow->copy()->subDays($i)->startOfDay();

            $daySessions = $sessions->filter(function ($session) use ($date, $timezone) {
                // Get the raw timestamp and create a new Carbon instance in user timezone
                // This works around issues with setTestNow affecting timezone conversions
                $timestamp = $session->completed_at->getTimestamp();
                $sessionInTz = \Carbon\Carbon::createFromTimestamp($timestamp, $timezone);

                // Compare using date strings to ensure timezone is respected
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
     * Get weekly progression summary showing exercises worked on and their harder variations.
     *
     * @return array<int, array{path_name: string, exercises: array}>
     */
    public function getWeeklyProgressionSummary(int $days = 7): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        // Fetch weekly session data
        $sessionExercises = SessionExercise::query()
            ->whereHas('session', function ($query) use ($startDate, $endDate) {
                $query->where('user_id', $this->id)
                    ->completed()
                    ->whereBetween('completed_at', [$startDate, $endDate]);
            })
            ->with(['exercise.progression'])
            ->get();

        if ($sessionExercises->isEmpty()) {
            return [];
        }

        // Group session data by exercise_id
        $sessionDataByExercise = $sessionExercises->groupBy('exercise_id');

        // Get unique exercises with progressions from this week's sessions
        $workedExercises = $sessionExercises
            ->pluck('exercise')
            ->unique('id')
            ->filter(fn ($exercise) => $exercise->progression && $exercise->progression->progression_path_name);

        // Build progression paths
        $progressionPaths = [];

        foreach ($workedExercises as $exercise) {
            $pathName = $exercise->progression->progression_path_name;

            if (isset($progressionPaths[$pathName])) {
                continue; // Already processed this path
            }

            // Get this exercise + all harder variations
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

    public function getWeeklyStandaloneExercises(int $days = 7): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        // Fetch weekly session data
        $sessionExercises = SessionExercise::query()
            ->whereHas('session', function ($query) use ($startDate, $endDate) {
                $query->where('user_id', $this->id)
                    ->completed()
                    ->whereBetween('completed_at', [$startDate, $endDate]);
            })
            ->with(['exercise.progression'])
            ->get();

        if ($sessionExercises->isEmpty()) {
            return [];
        }

        // Group session data by exercise_id
        $sessionDataByExercise = $sessionExercises->groupBy('exercise_id');

        // Get unique exercises WITHOUT progressions from this week's sessions
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

    /**
     * Calculate the current streak for a specific exercise.
     */
    public function getExerciseStreak(int $exerciseId): int
    {
        $streak = 0;
        $currentDate = $this->now()->startOfDay();
        $timezone = $this->timezone ?? 'America/Los_Angeles';

        while (true) {
            $hasExercise = SessionExercise::query()
                ->whereHas('session', function ($query) use ($currentDate, $timezone) {
                    $query->where('user_id', $this->id)
                        ->completed()
                        ->onDate($currentDate, $timezone);
                })
                ->where('exercise_id', $exerciseId)
                ->exists();

            if (! $hasExercise) {
                break;
            }

            $streak++;
            $currentDate = $currentDate->copy()->subDay();
        }

        return $streak;
    }

    /**
     * Get comprehensive progression data for gantt chart display.
     * Groups exercises by progression path with daily breakdown and streaks.
     *
     * @return array{progressions: array, standalone: array}
     */
    public function getProgressionGanttData(int $days = 7): array
    {
        $userNow = $this->now();
        $startDate = $userNow->copy()->subDays($days - 1)->startOfDay();
        $endDate = $userNow->copy()->endOfDay();
        $timezone = $this->timezone ?? 'America/Los_Angeles';

        // Convert to UTC for database query
        $startDateUtc = $startDate->copy()->timezone('UTC');
        $endDateUtc = $endDate->copy()->timezone('UTC');

        // Fetch all sessions that have been started (including in-progress and completed)
        // This allows partial progress to count even if the session was abandoned
        $sessions = $this->sessions()
            ->whereNotNull('started_at')
            ->where(function ($query) use ($startDateUtc, $endDateUtc) {
                $query->whereBetween('started_at', [$startDateUtc, $endDateUtc])
                    ->orWhereBetween('completed_at', [$startDateUtc, $endDateUtc]);
            })
            ->with(['sessionExercises.exercise.progression'])
            ->get();

        // Build daily exercise data
        $dailyData = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $userNow->copy()->subDays($i)->startOfDay();
            $dailyData[] = [
                'date' => $date,
                'dayName' => $date->format('D'),
                'exercises' => [], // Will be keyed by exercise_id
            ];
        }

        // Populate daily exercise durations
        // Only count exercises that have been individually completed (have completed_at set)
        foreach ($sessions as $session) {
            foreach ($session->sessionExercises as $sessionExercise) {
                // Skip exercises that haven't been completed yet
                if (! $sessionExercise->completed_at) {
                    continue;
                }

                // Use the exercise's completion timestamp for accurate day assignment
                $timestamp = $sessionExercise->completed_at->getTimestamp();
                $exerciseInTz = \Carbon\Carbon::createFromTimestamp($timestamp, $timezone);

                foreach ($dailyData as $dayIndex => &$day) {
                    if ($exerciseInTz->toDateString() === $day['date']->toDateString()) {
                        $exerciseId = $sessionExercise->exercise_id;
                        if (! isset($day['exercises'][$exerciseId])) {
                            $day['exercises'][$exerciseId] = 0;
                        }
                        $day['exercises'][$exerciseId] += $sessionExercise->duration_seconds ?? 0;
                    }
                }
                unset($day); // Break the reference to avoid PHP gotcha
            }
        }

        // Collect all unique exercises from completed session exercises in the week
        $allExercises = $sessions->flatMap(fn ($s) => $s->sessionExercises->filter(fn ($se) => $se->completed_at)->pluck('exercise'))
            ->unique('id')
            ->keyBy('id');

        // Group exercises by progression path
        $progressionPaths = [];
        $standaloneExercises = [];
        $processedProgressionPaths = [];
        $processedExerciseIds = []; // Track which exercises have been added to a progression

        foreach ($allExercises as $exercise) {
            $progression = $exercise->progression;

            if ($progression && $progression->progression_path_name) {
                $pathName = $progression->progression_path_name;

                // Skip if we've already processed this path
                if (isset($processedProgressionPaths[$pathName])) {
                    // But still mark this exercise as processed so it doesn't go to standalone
                    $processedExerciseIds[$exercise->id] = true;

                    continue;
                }

                $processedProgressionPaths[$pathName] = true;

                // Get ALL exercises in this progression path by querying the database
                // This is more reliable than chain traversal which can break if links are missing
                $pathExercises = Exercise::query()
                    ->whereHas('progression', function ($query) use ($pathName) {
                        $query->where('progression_path_name', $pathName);
                    })
                    ->with('progression')
                    ->get()
                    ->sortBy(fn ($ex) => $ex->progression->order ?? 0)
                    ->values();

                // Find which exercises were actually done this week
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

                    // Only include if done this week
                    if ($weeklyTotal > 0) {
                        $exercisesInPath[] = [
                            'id' => $exerciseId,
                            'name' => $pathExercise->name,
                            'order' => $index, // 0 = easiest in path
                            'total_in_path' => count($pathExercises),
                            'weekly_seconds' => $weeklyTotal,
                            'daily_seconds' => $dailySeconds,
                            'streak' => $this->getExerciseStreak($exerciseId),
                        ];
                    }
                    // Mark all exercises in path as processed (even if not done this week)
                    $processedExerciseIds[$exerciseId] = true;
                }

                if (! empty($exercisesInPath)) {
                    $progressionPaths[$pathName] = [
                        'path_name' => $pathName,
                        'exercises' => $exercisesInPath,
                    ];
                }
            } else {
                // Standalone exercise - skip if already added to a progression path
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
                        'streak' => $this->getExerciseStreak($exerciseId),
                    ];
                }
            }
        }

        // Build day labels and calculate daily/weekly totals
        $dayLabels = [];
        $dailyTotals = [];
        $weeklyTotal = 0;

        foreach ($dailyData as $day) {
            $dayLabels[] = substr($day['dayName'], 0, 1);
            $dayTotal = array_sum($day['exercises']);
            $dailyTotals[] = $dayTotal;
            $weeklyTotal += $dayTotal;
        }

        return [
            'progressions' => array_values($progressionPaths),
            'standalone' => $standaloneExercises,
            'dayLabels' => $dayLabels,
            'dailyTotals' => $dailyTotals,
            'weeklyTotal' => $weeklyTotal,
        ];
    }
}
