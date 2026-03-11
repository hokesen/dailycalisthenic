<?php

namespace App\Enums;

enum LiftCategory: string
{
    case Bench = 'bench';
    case Overhead = 'overhead';
    case Deadlift = 'deadlift';
    case Squat = 'squat';
    case Row = 'row';
    case Clean = 'clean';

    public function label(): string
    {
        return match ($this) {
            self::Bench => 'Bench Press',
            self::Overhead => 'Overhead Press',
            self::Deadlift => 'Deadlift',
            self::Squat => 'Squat',
            self::Row => 'Barbell Row',
            self::Clean => 'Power Clean',
        };
    }

    public function movementPattern(): string
    {
        return match ($this) {
            self::Bench, self::Overhead => 'push',
            self::Deadlift, self::Row => 'pull',
            self::Squat => 'legs',
            self::Clean => 'full_body',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
