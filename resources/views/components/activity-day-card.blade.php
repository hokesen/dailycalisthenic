@props(['day'])

@php
    $totalSeconds = collect($day['exercises'])->sum('total_seconds');
    $totalMinutes = $totalSeconds > 0 ? round($totalSeconds / 60) : 0;
@endphp

<div class="flex flex-col items-center" x-data="{ expanded: false }">
    <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">{{ $day['dayName'] }}</div>
    <div
        @click="expanded = !expanded"
        class="w-full min-h-[80px] rounded-lg border-2 flex flex-col items-center justify-center p-2 cursor-pointer transition-colors {{ $day['hasSession'] ? 'bg-green-50 dark:bg-green-900/20 border-green-500 dark:border-green-600 hover:bg-green-100 dark:hover:bg-green-900/30' : 'bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
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

    <!-- Expanded exercise list -->
    <div
        x-show="expanded"
        x-transition
        class="mt-2 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 shadow-lg"
        x-cloak
    >
        @if (count($day['exercises']) > 0)
            <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Exercises:</div>
            <div class="space-y-1">
                @foreach ($day['exercises'] as $exercise)
                    @php
                        $minutes = round($exercise['total_seconds'] / 60);
                    @endphp
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-600 dark:text-gray-400">{{ $exercise['name'] }}</span>
                        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $minutes }} min</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-xs text-gray-500 dark:text-gray-400">No exercises</div>
        @endif
    </div>
</div>
