<?php

namespace App\Models;

use App\Enums\SessionStatus;
use App\Models\Concerns\PivotColumns;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_template_id',
        'training_program_enrollment_id',
        'program_day_key',
        'name',
        'notes',
        'started_at',
        'completed_at',
        'total_duration_seconds',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'status' => SessionStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SessionTemplate::class, 'session_template_id');
    }

    public function trainingProgramEnrollment(): BelongsTo
    {
        return $this->belongsTo(TrainingProgramEnrollment::class);
    }

    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'session_exercises')
            ->withPivot(PivotColumns::SESSION_EXERCISES)
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function sessionExercises(): HasMany
    {
        return $this->hasMany(SessionExercise::class)->orderBy('order');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', SessionStatus::Completed)->whereNotNull('completed_at');
    }

    /**
     * Scope sessions that should count as real activity.
     *
     * Completed sessions count even if older test data omitted durations.
     * In-progress sessions only count once actual work has been recorded.
     */
    public function scopeCountsTowardActivity(Builder $query): Builder
    {
        return $query->where(function (Builder $activityQuery) {
            $activityQuery
                ->where(function (Builder $completedQuery) {
                    $completedQuery
                        ->where('status', SessionStatus::Completed)
                        ->whereNotNull('completed_at');
                })
                ->orWhere(function (Builder $inProgressQuery) {
                    $inProgressQuery
                        ->where('status', SessionStatus::InProgress)
                        ->where(function (Builder $workQuery) {
                            $workQuery
                                ->where('total_duration_seconds', '>', 0)
                                ->orWhereHas('sessionExercises', function (Builder $exerciseQuery) {
                                    $exerciseQuery->whereNotNull('completed_at');
                                });
                        });
                });
        });
    }

    public function scopeOnDate($query, $date, $timezone = 'America/Los_Angeles')
    {
        // Convert the date to the user's timezone if needed
        $dateInTimezone = $date instanceof \Carbon\Carbon
            ? $date->copy()->timezone($timezone)
            : \Carbon\Carbon::parse($date, $timezone);

        $startOfDay = $dateInTimezone->copy()->startOfDay()->timezone('UTC');
        $endOfDay = $dateInTimezone->copy()->endOfDay()->timezone('UTC');

        return $query->whereBetween('completed_at', [$startOfDay, $endOfDay]);
    }

    /**
     * Scope to filter sessions within a date range.
     */
    public function scopeForDateRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('completed_at', [$start, $end]);
    }

    /**
     * Scope to eager load session exercises with their related exercise data.
     */
    public function scopeWithExercises(Builder $query): Builder
    {
        return $query->with(['sessionExercises.exercise']);
    }
}
