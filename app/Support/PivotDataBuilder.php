<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PivotDataBuilder
{
    /**
     * Extract session template exercise pivot data from a pivot instance
     */
    public static function fromSessionTemplateExercisePivot(Pivot $pivot): array
    {
        return [
            'order' => $pivot->order,
            'duration_seconds' => $pivot->duration_seconds,
            'rest_after_seconds' => $pivot->rest_after_seconds,
            'sets' => $pivot->sets,
            'reps' => $pivot->reps,
            'notes' => $pivot->notes,
        ];
    }

    /**
     * Build session template exercise pivot data from array
     */
    public static function buildSessionTemplateExercisePivot(array $data, ?int $defaultOrder = null): array
    {
        return [
            'order' => $data['order'] ?? $defaultOrder ?? 0,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'rest_after_seconds' => $data['rest_after_seconds'] ?? null,
            'sets' => $data['sets'] ?? null,
            'reps' => $data['reps'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * Build default session template exercise pivot data
     */
    public static function defaultSessionTemplateExercisePivot(int $order, ?int $defaultRestSeconds = null): array
    {
        return [
            'order' => $order,
            'duration_seconds' => null,
            'rest_after_seconds' => $defaultRestSeconds,
            'sets' => null,
            'reps' => null,
            'notes' => null,
        ];
    }
}
