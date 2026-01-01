<?php

namespace App\Enums;

enum ExerciseCategory: string
{
    case Push = 'push';
    case Pull = 'pull';
    case Legs = 'legs';
    case Core = 'core';
    case FullBody = 'full_body';
    case Cardio = 'cardio';
    case Flexibility = 'flexibility';

    public function label(): string
    {
        return str($this->value)->title()->replace('_', ' ')->toString();
    }

    public function color(): string
    {
        return match ($this) {
            self::Push => 'danger',
            self::Pull => 'success',
            self::Legs => 'warning',
            self::Core => 'info',
            self::FullBody => 'primary',
            self::Cardio => 'gray',
            self::Flexibility => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
