<?php

namespace App\Enums;

enum ExerciseTempo: string
{
    case Slow = 'slow';
    case Normal = 'normal';
    case Fast = 'fast';
    case Explosive = 'explosive';

    public function label(): string
    {
        return match ($this) {
            self::Slow => 'Slow & Controlled',
            self::Normal => 'Normal Pace',
            self::Fast => 'Fast',
            self::Explosive => 'Explosive',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Slow => 'Focus on form, control each movement',
            self::Normal => 'Standard training pace',
            self::Fast => 'Quick tempo, maintain form',
            self::Explosive => 'Maximum speed and power',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
