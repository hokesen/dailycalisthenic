<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assessment_slug',
        'recorded_on',
        'primary_result_seconds',
        'results',
        'split_results',
        'derived_status',
        'summary_label',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'results' => 'array',
            'split_results' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
