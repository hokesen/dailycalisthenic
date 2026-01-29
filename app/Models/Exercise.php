<?php

namespace App\Models;

use App\Enums\ExerciseCategory;
use App\Enums\ExerciseDifficulty;
use App\Models\Concerns\HasProgressionVariations;
use App\Models\Concerns\PivotColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Exercise extends Model
{
    use HasFactory, HasProgressionVariations;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'instructions',
        'difficulty_level',
        'category',
        'default_duration_seconds',
    ];

    protected $casts = [
        'category' => ExerciseCategory::class,
        'difficulty_level' => ExerciseDifficulty::class,
    ];

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
            ->withPivot(PivotColumns::SESSION_TEMPLATE_EXERCISES)
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(Session::class, 'session_exercises')
            ->withPivot(PivotColumns::SESSION_EXERCISES)
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

    /**
     * Scope to eager load progression relationships.
     */
    public function scopeWithProgression(Builder $query): Builder
    {
        return $query->with(['progression.easierExercise', 'progression.harderExercise']);
    }

    /**
     * Scope to filter exercises by progression path name.
     */
    public function scopeInProgressionPath(Builder $query, string $pathName): Builder
    {
        return $query->whereHas('progression', function ($q) use ($pathName) {
            $q->where('progression_path_name', $pathName);
        });
    }
}
