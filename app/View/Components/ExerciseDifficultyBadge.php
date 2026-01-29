<?php

namespace App\View\Components;

use App\Enums\ExerciseDifficulty;
use App\Support\ColorMapper;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ExerciseDifficultyBadge extends Component
{
    public string $colorClasses;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ExerciseDifficulty $difficulty
    ) {
        $this->colorClasses = ColorMapper::difficultyColor($this->difficulty);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.exercise-difficulty-badge');
    }
}
