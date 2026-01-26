<?php

namespace Tests\Unit\Support;

use App\Support\TimezoneConverter;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TimezoneConverterTest extends TestCase
{
    public function test_convert_date_range_to_utc_converts_correctly(): void
    {
        $startDate = Carbon::create(2026, 1, 15, 0, 0, 0, 'America/Los_Angeles');
        $endDate = Carbon::create(2026, 1, 15, 23, 59, 59, 'America/Los_Angeles');

        [$startUtc, $endUtc] = TimezoneConverter::convertDateRangeToUtc($startDate, $endDate, 'America/Los_Angeles');

        $this->assertEquals('UTC', $startUtc->timezoneName);
        $this->assertEquals('UTC', $endUtc->timezoneName);
        $this->assertEquals(8, $startUtc->hour);
        $this->assertEquals(7, $endUtc->hour);
    }

    public function test_convert_date_range_to_utc_with_different_timezone(): void
    {
        $startDate = Carbon::create(2026, 1, 15, 0, 0, 0, 'America/New_York');
        $endDate = Carbon::create(2026, 1, 15, 23, 59, 59, 'America/New_York');

        [$startUtc, $endUtc] = TimezoneConverter::convertDateRangeToUtc($startDate, $endDate, 'America/New_York');

        $this->assertEquals('UTC', $startUtc->timezoneName);
        $this->assertEquals('UTC', $endUtc->timezoneName);
        $this->assertEquals(5, $startUtc->hour);
        $this->assertEquals(4, $endUtc->hour);
    }

    public function test_to_user_timezone_converts_correctly(): void
    {
        $utcTime = Carbon::create(2026, 1, 15, 12, 0, 0, 'UTC');

        $userTime = TimezoneConverter::toUserTimezone($utcTime, 'America/Los_Angeles');

        $this->assertEquals('America/Los_Angeles', $userTime->timezoneName);
        $this->assertEquals(4, $userTime->hour);
    }

    public function test_to_user_timezone_with_new_york(): void
    {
        $utcTime = Carbon::create(2026, 1, 15, 12, 0, 0, 'UTC');

        $userTime = TimezoneConverter::toUserTimezone($utcTime, 'America/New_York');

        $this->assertEquals('America/New_York', $userTime->timezoneName);
        $this->assertEquals(7, $userTime->hour);
    }

    public function test_from_timestamp_to_timezone_converts_correctly(): void
    {
        $timestamp = Carbon::create(2026, 1, 15, 12, 0, 0, 'UTC')->getTimestamp();

        $userTime = TimezoneConverter::fromTimestampToTimezone($timestamp, 'America/Los_Angeles');

        $this->assertEquals('America/Los_Angeles', $userTime->timezoneName);
        $this->assertEquals(4, $userTime->hour);
    }

    public function test_from_timestamp_to_timezone_with_new_york(): void
    {
        $timestamp = Carbon::create(2026, 1, 15, 12, 0, 0, 'UTC')->getTimestamp();

        $userTime = TimezoneConverter::fromTimestampToTimezone($timestamp, 'America/New_York');

        $this->assertEquals('America/New_York', $userTime->timezoneName);
        $this->assertEquals(7, $userTime->hour);
    }
}
