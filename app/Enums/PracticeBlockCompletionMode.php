<?php

namespace App\Enums;

enum PracticeBlockCompletionMode: string
{
    case Timed = 'timed';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Timed => 'Timed',
            self::Manual => 'Manual',
        };
    }
}
