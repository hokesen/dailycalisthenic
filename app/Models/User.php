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

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
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
        $activityService = app(\App\Services\UserActivityService::class);

        return $activityService->getPastDaysWithActivity($this, $days);
    }

    /**
     * Calculate the current streak of consecutive days with completed sessions.
     */
    public function getCurrentStreak(): int
    {
        $streakService = app(\App\Services\StreakService::class);

        return $streakService->calculateStreak($this);
    }

    /**
     * Calculate the potential streak if the user completes a session today.
     * Returns the streak they would have after completing today's practice.
     */
    public function getPotentialStreak(): int
    {
        $streakService = app(\App\Services\StreakService::class);

        return $streakService->calculatePotentialStreak($this);
    }

    /**
     * Get weekly exercise breakdown with minutes per exercise for each day.
     *
     * @return array<int, array{date: \Carbon\Carbon, dayName: string, hasSession: bool, exercises: array}>
     */
    public function getWeeklyExerciseBreakdown(int $days = 7): array
    {
        $activityService = app(\App\Services\UserActivityService::class);

        return $activityService->getWeeklyExerciseBreakdown($this, $days);
    }

    /**
     * Get weekly progression summary showing exercises worked on and their harder variations.
     *
     * @return array<int, array{path_name: string, exercises: array}>
     */
    public function getWeeklyProgressionSummary(int $days = 7): array
    {
        $progressionService = app(\App\Services\ProgressionAnalyticsService::class);

        return $progressionService->getWeeklyProgressionSummary($this, $days);
    }

    public function getWeeklyStandaloneExercises(int $days = 7): array
    {
        $progressionService = app(\App\Services\ProgressionAnalyticsService::class);

        return $progressionService->getWeeklyStandaloneExercises($this, $days);
    }

    /**
     * Calculate the current streak for a specific exercise.
     */
    public function getExerciseStreak(int $exerciseId): int
    {
        $streakService = app(\App\Services\StreakService::class);

        return $streakService->calculateExerciseStreak($this, $exerciseId);
    }

    /**
     * Get comprehensive progression data for gantt chart display.
     * Groups exercises by progression path with daily breakdown and streaks.
     *
     * @return array{progressions: array, standalone: array}
     */
    public function getProgressionGanttData(int $days = 7): array
    {
        $progressionService = app(\App\Services\ProgressionAnalyticsService::class);

        return $progressionService->getProgressionGanttData($this, $days);
    }
}
