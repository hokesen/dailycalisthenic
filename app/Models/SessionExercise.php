<?php

namespace App\Models;

use App\Enums\ExerciseIntensity;
use App\Enums\ExerciseTempo;
use App\Enums\LiftCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'exercise_id',
        'order',
        'duration_seconds',
        'notes',
        'difficulty_rating',
        'started_at',
        'completed_at',
        'tempo',
        'intensity',
        'weight_lbs',
        'reps_completed',
        'sets_completed',
        'lift_category',
        'is_personal_record',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'tempo' => ExerciseTempo::class,
            'intensity' => ExerciseIntensity::class,
            'lift_category' => LiftCategory::class,
            'is_personal_record' => 'boolean',
            'weight_lbs' => 'float',
            'reps_completed' => 'integer',
            'sets_completed' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
