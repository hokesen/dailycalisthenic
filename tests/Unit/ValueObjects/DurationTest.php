<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Duration;
use PHPUnit\Framework\TestCase;

class DurationTest extends TestCase
{
    public function test_from_seconds_creates_duration(): void
    {
        $duration = Duration::fromSeconds(120);

        $this->assertInstanceOf(Duration::class, $duration);
        $this->assertEquals(120, $duration->toSeconds());
    }

    public function test_to_minutes_returns_rounded_minutes(): void
    {
        $duration = Duration::fromSeconds(90);

        $this->assertEquals(2, $duration->toMinutes());
    }

    public function test_to_minutes_rounds_correctly(): void
    {
        $this->assertEquals(0, Duration::fromSeconds(29)->toMinutes());
        $this->assertEquals(1, Duration::fromSeconds(30)->toMinutes());
        $this->assertEquals(1, Duration::fromSeconds(89)->toMinutes());
        $this->assertEquals(2, Duration::fromSeconds(90)->toMinutes());
    }

    public function test_format_displays_minutes_for_short_durations(): void
    {
        $duration = Duration::fromSeconds(120);

        $this->assertEquals('2m', $duration->format());
    }

    public function test_format_displays_hours_and_minutes_for_long_durations(): void
    {
        $duration = Duration::fromSeconds(3665); // 61 minutes, 5 seconds

        $this->assertEquals('1h 1m', $duration->format());
    }

    public function test_format_displays_only_hours_when_no_remaining_minutes(): void
    {
        $duration = Duration::fromSeconds(3600); // Exactly 1 hour

        $this->assertEquals('1h', $duration->format());
    }

    public function test_format_handles_zero_duration(): void
    {
        $duration = Duration::fromSeconds(0);

        $this->assertEquals('0m', $duration->format());
    }

    public function test_constructor_accepts_seconds(): void
    {
        $duration = new Duration(180);

        $this->assertEquals(180, $duration->toSeconds());
        $this->assertEquals(3, $duration->toMinutes());
    }
}
