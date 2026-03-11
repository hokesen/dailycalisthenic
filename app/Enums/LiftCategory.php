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
            self::Bench => 'push',
            self::Overhead => 'push',
            self::Deadlift => 'pull',
            self::Squat => 'legs',
            self::Row => 'pull',
            self::Clean => 'full_body',
        };
    }
}
