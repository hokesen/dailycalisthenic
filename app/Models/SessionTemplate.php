<?php

namespace App\Models;

use App\Enums\TrainingDiscipline;
use App\Models\Concerns\PivotColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'notes',
        'discipline',
        'default_rest_seconds',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'discipline' => TrainingDiscipline::class,
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'session_template_exercises')
            ->withPivot(PivotColumns::SESSION_TEMPLATE_EXERCISES)
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function practiceBlocks(): HasMany
    {
        return $this->hasMany(PracticeBlock::class)->orderBy('sort_order');
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

    public function calculateDurationMinutes(): int
    {
        if ($this->relationLoaded('practiceBlocks') && $this->practiceBlocks->isNotEmpty()) {
            $totalSeconds = $this->practiceBlocks->sum(function (PracticeBlock $block) {
                $duration = ($block->duration_seconds ?? 0) * max(1, $block->repeats);
                $rest = ($block->rest_after_seconds ?? 0) * max(0, $block->repeats - 1);

                return $duration + $rest;
            });

            return (int) ceil($totalSeconds / 60);
        }

        if ($this->exercises->isEmpty()) {
            return 0;
        }

        $totalSeconds = $this->exercises->sum(function ($exercise) {
            $duration = $exercise->pivot->duration_seconds ?? 0;
            $rest = $exercise->pivot->rest_after_seconds ?? 0;

            return $duration + $rest;
        });

        return (int) ceil($totalSeconds / 60);
    }
}
