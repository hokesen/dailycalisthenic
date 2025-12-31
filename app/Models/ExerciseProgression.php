<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseProgression extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercise_id',
        'easier_exercise_id',
        'harder_exercise_id',
        'order',
        'progression_path_name',
    ];

    protected $casts = [];

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function easierExercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class, 'easier_exercise_id');
    }

    public function harderExercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class, 'harder_exercise_id');
    }

    public function scopeForPath($query, string $pathName)
    {
        return $query->where('progression_path_name', $pathName);
    }
}
