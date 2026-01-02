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
        $today = now()->startOfDay();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $hasSession = $this->sessions()
                ->completed()
                ->onDate($date)
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
        $currentDate = now()->startOfDay();

        while (true) {
            $hasSession = $this->sessions()
                ->completed()
                ->onDate($currentDate)
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
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        // Single query to fetch all completed sessions with exercises
        $sessions = $this->sessions()
            ->completed()
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->with(['sessionExercises.exercise'])
            ->get();

        // Build daily breakdown
        $breakdown = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();

            $daySessions = $sessions->filter(function ($session) use ($date) {
                return $session->completed_at->isSameDay($date);
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
}
