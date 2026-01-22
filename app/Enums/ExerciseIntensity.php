<?php

namespace App\Enums;

enum ExerciseIntensity: string
{
    case Recovery = 'recovery';
    case Easy = 'easy';
    case Moderate = 'moderate';
    case Hard = 'hard';
    case Maximum = 'maximum';

    public function label(): string
    {
        return str($this->value)->title()->toString();
    }

    public function description(): string
    {
        return match ($this) {
            self::Recovery => 'Very light effort, conversational pace',
            self::Easy => 'Light effort, can hold conversation',
            self::Moderate => 'Moderate effort, breathing harder',
            self::Hard => 'Hard effort, difficult to talk',
            self::Maximum => 'Maximum effort, all-out',
        };
    }

    public function heartRateZone(): ?int
    {
        return match ($this) {
            self::Recovery => 1,
            self::Easy => 2,
            self::Moderate => 3,
            self::Hard => 4,
            self::Maximum => 5,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Recovery => 'text-blue-400',
            self::Easy => 'text-green-400',
            self::Moderate => 'text-yellow-400',
            self::Hard => 'text-orange-400',
            self::Maximum => 'text-red-400',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
