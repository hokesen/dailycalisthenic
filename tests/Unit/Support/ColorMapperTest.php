<?php

namespace Tests\Unit\Support;

use App\Enums\ExerciseCategory;
use App\Enums\ExerciseDifficulty;
use App\Support\ColorMapper;
use PHPUnit\Framework\TestCase;

class ColorMapperTest extends TestCase
{
    public function test_category_color_returns_correct_classes_for_push(): void
    {
        $classes = ColorMapper::categoryColor(ExerciseCategory::Push);

        $this->assertEquals('bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', $classes);
    }

    public function test_category_color_returns_correct_classes_for_pull(): void
    {
        $classes = ColorMapper::categoryColor(ExerciseCategory::Pull);

        $this->assertEquals('bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', $classes);
    }

    public function test_category_color_returns_correct_classes_for_legs(): void
    {
        $classes = ColorMapper::categoryColor(ExerciseCategory::Legs);

        $this->assertEquals('bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400', $classes);
    }

    public function test_category_color_returns_correct_classes_for_core(): void
    {
        $classes = ColorMapper::categoryColor(ExerciseCategory::Core);

        $this->assertEquals('bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', $classes);
    }

    public function test_category_color_returns_correct_classes_for_full_body(): void
    {
        $classes = ColorMapper::categoryColor(ExerciseCategory::FullBody);

        $this->assertEquals('bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400', $classes);
    }

    public function test_category_color_returns_default_for_other_categories(): void
    {
        $classes = ColorMapper::categoryColor(ExerciseCategory::Cardio);

        $this->assertEquals('bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400', $classes);
    }

    public function test_difficulty_color_returns_correct_classes_for_beginner(): void
    {
        $classes = ColorMapper::difficultyColor(ExerciseDifficulty::Beginner);

        $this->assertEquals('bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', $classes);
    }

    public function test_difficulty_color_returns_correct_classes_for_intermediate(): void
    {
        $classes = ColorMapper::difficultyColor(ExerciseDifficulty::Intermediate);

        $this->assertEquals('bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', $classes);
    }

    public function test_difficulty_color_returns_correct_classes_for_advanced(): void
    {
        $classes = ColorMapper::difficultyColor(ExerciseDifficulty::Advanced);

        $this->assertEquals('bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', $classes);
    }

    public function test_difficulty_color_returns_correct_classes_for_expert(): void
    {
        $classes = ColorMapper::difficultyColor(ExerciseDifficulty::Expert);

        $this->assertEquals('bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400', $classes);
    }

    public function test_progression_position_color_returns_green_for_position_one(): void
    {
        $classes = ColorMapper::progressionPositionColor(1);

        $this->assertEquals('bg-green-500 dark:bg-green-600', $classes);
    }

    public function test_progression_position_color_returns_blue_for_position_two(): void
    {
        $classes = ColorMapper::progressionPositionColor(2);

        $this->assertEquals('bg-blue-500 dark:bg-blue-600', $classes);
    }

    public function test_progression_position_color_returns_yellow_for_position_three(): void
    {
        $classes = ColorMapper::progressionPositionColor(3);

        $this->assertEquals('bg-yellow-500 dark:bg-yellow-600', $classes);
    }

    public function test_progression_position_color_returns_red_for_higher_positions(): void
    {
        $this->assertEquals('bg-red-500 dark:bg-red-600', ColorMapper::progressionPositionColor(4));
        $this->assertEquals('bg-red-500 dark:bg-red-600', ColorMapper::progressionPositionColor(5));
        $this->assertEquals('bg-red-500 dark:bg-red-600', ColorMapper::progressionPositionColor(10));
    }

    public function test_progression_level_indicator_color_returns_correct_class(): void
    {
        $this->assertEquals('bg-green-500', ColorMapper::progressionLevelIndicatorColor(1));
        $this->assertEquals('bg-blue-500', ColorMapper::progressionLevelIndicatorColor(2));
        $this->assertEquals('bg-yellow-500', ColorMapper::progressionLevelIndicatorColor(3));
        $this->assertEquals('bg-red-500', ColorMapper::progressionLevelIndicatorColor(4));
    }
}
