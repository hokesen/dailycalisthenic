<?php

namespace App\Models;

use App\Enums\SessionStatus;
use App\Models\Concerns\PivotColumns;
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

    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('completed_at', $date);
    }
}
