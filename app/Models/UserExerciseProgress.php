<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserExerciseProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exercise_id',
        'status',
        'best_sets',
        'best_reps',
        'best_duration_seconds',
        'mastered_at',
        'started_at',
        'notes',
    ];

    protected $casts = [
        'mastered_at' => 'datetime',
        'started_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('status', 'current');
    }

    public function scopeMastered($query)
    {
        return $query->where('status', 'mastered');
    }
}
