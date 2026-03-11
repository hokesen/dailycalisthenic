<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeditationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'duration_seconds',
        'technique',
        'breath_cycles_completed',
        'notes',
        'practiced_at',
    ];

    protected function casts(): array
    {
        return [
            'practiced_at' => 'datetime',
            'duration_seconds' => 'integer',
            'breath_cycles_completed' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
