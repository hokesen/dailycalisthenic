<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionTemplateExercise extends Model
{
    use HasFactory;
    protected $fillable = [
        'session_template_id',
        'exercise_id',
        'order',
        'duration_seconds',
        'rest_after_seconds',
        'sets',
        'reps',
        'notes',
    ];

    protected $casts = [];

    public function sessionTemplate(): BelongsTo
    {
        return $this->belongsTo(SessionTemplate::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
