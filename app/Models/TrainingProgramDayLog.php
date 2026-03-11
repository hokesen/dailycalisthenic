<?php

namespace App\Models;

use App\Enums\TrainingProgramDayStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingProgramDayLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_program_enrollment_id',
        'program_day_key',
        'scheduled_for',
        'actual_date',
        'status',
        'session_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'actual_date' => 'date',
            'status' => TrainingProgramDayStatus::class,
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(TrainingProgramEnrollment::class, 'training_program_enrollment_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
