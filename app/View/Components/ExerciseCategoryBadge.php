<?php

namespace App\View\Components;

use App\Enums\ExerciseCategory;
use App\Support\ColorMapper;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ExerciseCategoryBadge extends Component
{
    public string $colorClasses;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ExerciseCategory $category
    ) {
        $this->colorClasses = ColorMapper::categoryColor($this->category);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.exercise-category-badge');
    }
}
