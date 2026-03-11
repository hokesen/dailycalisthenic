<?php

namespace App\Models;

use App\Enums\PracticeBlockCompletionMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_template_id',
        'exercise_id',
        'sort_order',
        'title',
        'completion_mode',
        'duration_seconds',
        'rest_after_seconds',
        'repeats',
        'distance_label',
        'target_cue',
        'setup_text',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'completion_mode' => PracticeBlockCompletionMode::class,
            'metadata' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SessionTemplate::class, 'session_template_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
