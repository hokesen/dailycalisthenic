<?php

namespace App\Enums;

enum TrainingProgramDayStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Skipped = 'skipped';
    case Moved = 'moved';
}
