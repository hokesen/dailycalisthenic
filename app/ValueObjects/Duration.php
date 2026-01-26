<?php

namespace App\ValueObjects;

class Duration
{
    public function __construct(
        private readonly int $seconds
    ) {}

    public static function fromSeconds(int $seconds): self
    {
        return new self($seconds);
    }

    public function toSeconds(): int
    {
        return $this->seconds;
    }

    public function toMinutes(): int
    {
        return (int) round($this->seconds / 60);
    }

    public function format(): string
    {
        if ($this->seconds < 60) {
            return '0m';
        }

        $hours = (int) floor($this->seconds / 3600);
        $remainingSeconds = $this->seconds % 3600;
        $minutes = (int) round($remainingSeconds / 60);

        if ($hours > 0) {
            if ($minutes > 0) {
                return "{$hours}h {$minutes}m";
            }

            return "{$hours}h";
        }

        return "{$minutes}m";
    }
}
