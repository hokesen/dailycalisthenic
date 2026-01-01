<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'instructions',
        'difficulty_level',
        'category',
        'default_duration_seconds',
    ];

    protected $casts = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function progression(): HasOne
    {
        return $this->hasOne(ExerciseProgression::class);
    }

    public function sessionTemplates(): BelongsToMany
    {
        return $this->belongsToMany(SessionTemplate::class, 'session_template_exercises')
            ->withPivot(['order', 'duration_seconds', 'rest_after_seconds', 'sets', 'reps', 'notes'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(Session::class, 'session_exercises')
            ->withPivot(['order', 'duration_seconds', 'notes', 'difficulty_rating', 'started_at', 'completed_at'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserExerciseProgress::class);
    }

    public function scopeSystem($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeAvailableFor($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->whereNull('user_id')
                ->orWhere('user_id', $user->id);
        });
    }

    public function getEasierVariations(): array
    {
        $variations = [];
        $current = $this;

        while ($current->progression && $current->progression->easierExercise) {
            $easier = $current->progression->easierExercise;
            $variations[] = $easier;
            $current = $easier;
        }

        return $variations;
    }

    public function getHarderVariations(): array
    {
        $variations = [];
        $current = $this;

        while ($current->progression && $current->progression->harderExercise) {
            $harder = $current->progression->harderExercise;
            $variations[] = $harder;
            $current = $harder;
        }

        return $variations;
    }
}
