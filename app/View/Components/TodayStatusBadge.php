<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TodayStatusBadge extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public bool $hasPracticed,
        public bool $showLabel = true
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.today-status-badge');
    }
}
