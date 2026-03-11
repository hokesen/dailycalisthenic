<?php

namespace App\Enums;

enum TrainingDiscipline: string
{
    case General = 'general';
    case Soccer = 'soccer';
    case Meditation = 'meditation';
    case Lifting = 'lifting';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Calisthenics',
            self::Soccer => 'Soccer',
            self::Meditation => 'Meditation',
            self::Lifting => 'Lifting',
        };
    }
}
