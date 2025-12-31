<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

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
}
