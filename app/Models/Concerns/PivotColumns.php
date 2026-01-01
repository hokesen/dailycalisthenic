<?php

namespace App\Models\Concerns;

class PivotColumns
{
    /**
     * Pivot columns for session_template_exercises table
     */
    public const SESSION_TEMPLATE_EXERCISES = [
        'order',
        'duration_seconds',
        'rest_after_seconds',
        'sets',
        'reps',
        'notes',
    ];

    /**
     * Pivot columns for session_exercises table
     */
    public const SESSION_EXERCISES = [
        'order',
        'duration_seconds',
        'notes',
        'difficulty_rating',
        'started_at',
        'completed_at',
    ];
}
