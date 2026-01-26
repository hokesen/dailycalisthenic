<?php

namespace App\Support;

use App\Enums\ExerciseCategory;
use App\Enums\ExerciseDifficulty;

class ColorMapper
{
    public static function categoryColor(ExerciseCategory $category): string
    {
        return match ($category) {
            ExerciseCategory::Push => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            ExerciseCategory::Pull => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            ExerciseCategory::Legs => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
            ExerciseCategory::Core => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            ExerciseCategory::FullBody => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
        };
    }

    public static function difficultyColor(ExerciseDifficulty $difficulty): string
    {
        return match ($difficulty) {
            ExerciseDifficulty::Beginner => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            ExerciseDifficulty::Intermediate => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            ExerciseDifficulty::Advanced => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            ExerciseDifficulty::Expert => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        };
    }

    public static function progressionPositionColor(int $position): string
    {
        return match ($position) {
            1 => 'bg-green-500 dark:bg-green-600',
            2 => 'bg-blue-500 dark:bg-blue-600',
            3 => 'bg-yellow-500 dark:bg-yellow-600',
            default => 'bg-red-500 dark:bg-red-600',
        };
    }

    public static function progressionLevelIndicatorColor(int $position): string
    {
        return match ($position) {
            1 => 'bg-green-500',
            2 => 'bg-blue-500',
            3 => 'bg-yellow-500',
            default => 'bg-red-500',
        };
    }
}
