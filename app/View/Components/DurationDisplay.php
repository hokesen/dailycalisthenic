<?php

namespace App\View\Components;

use App\ValueObjects\Duration;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DurationDisplay extends Component
{
    public string $formatted;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public int $seconds,
        public string $format = 'auto'
    ) {
        $duration = Duration::fromSeconds($this->seconds);

        $this->formatted = match ($this->format) {
            'minutes' => $duration->toMinutes().'m',
            'hours' => $this->formatHours($duration),
            default => $duration->format(),
        };
    }

    /**
     * Format duration as hours.
     */
    protected function formatHours(Duration $duration): string
    {
        $totalMinutes = $duration->toMinutes();
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        if ($minutes === 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$minutes}m";
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.duration-display');
    }
}
