<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgramEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'program_slug',
        'starts_on',
        'team_practice_band',
        'is_active',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'is_active' => 'boolean',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dayLogs(): HasMany
    {
        return $this->hasMany(TrainingProgramDayLog::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }
}
