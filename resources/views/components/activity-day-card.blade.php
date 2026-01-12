@props(['day', 'dayIndex'])

@php
    $totalSeconds = collect($day['exercises'])->sum('total_seconds');
    $totalMinutes = $totalSeconds > 0 ? round($totalSeconds / 60) : 0;
@endphp

<div class="flex flex-col items-center">
    <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">{{ $day['dayName'] }}</div>
    <div
        @click="toggleDay({{ $dayIndex }})"
        class="w-full min-h-[80px] rounded-lg border-2 flex flex-col items-center justify-center p-2 cursor-pointer transition-colors {{ $day['hasSession'] ? 'bg-green-50 dark:bg-green-900/20 border-green-500 dark:border-green-600 hover:bg-green-100 dark:hover:bg-green-900/30' : 'bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
        :class="{ 'ring-2 ring-blue-500': selectedDay === {{ $dayIndex }} }"
    >
        @if ($day['hasSession'])
            <svg class="w-6 h-6 text-green-600 dark:text-green-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
            @if ($totalMinutes > 0)
                <div class="text-xs font-medium text-green-700 dark:text-green-400">{{ $totalMinutes }} min</div>
            @endif
        @else
            <div class="text-gray-400 dark:text-gray-600">-</div>
        @endif
    </div>
    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $day['date']->format('j') }}</div>
</div>
