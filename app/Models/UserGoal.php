<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sessions_per_week',
        'minimum_session_duration_minutes',
        'exercise_goals',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'exercise_goals' => 'array',
        'is_active' => 'boolean',
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('starts_at', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $date);
            });
    }
}
