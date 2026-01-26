<?php

namespace App\Support;

use Carbon\Carbon;

class TimezoneConverter
{
    public static function convertDateRangeToUtc(Carbon $startDate, Carbon $endDate, string $timezone): array
    {
        $startDateUtc = $startDate->copy()->timezone('UTC');
        $endDateUtc = $endDate->copy()->timezone('UTC');

        return [$startDateUtc, $endDateUtc];
    }

    public static function toUserTimezone(Carbon $utcTime, string $timezone): Carbon
    {
        return $utcTime->copy()->timezone($timezone);
    }

    public static function fromTimestampToTimezone(int $timestamp, string $timezone): Carbon
    {
        return Carbon::createFromTimestamp($timestamp, $timezone);
    }
}
