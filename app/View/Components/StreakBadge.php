<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StreakBadge extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public int $count,
        public ?int $potentialStreak = null,
        public string $size = 'default',
        public bool $showLabel = true
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.streak-badge');
    }
}
