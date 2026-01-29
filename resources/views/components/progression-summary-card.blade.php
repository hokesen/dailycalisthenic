@props(['progression'])

<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6 text-center">{{ ucwords($progression['path_name']) }} Progression</h3>

    <div class="flex flex-col md:flex-row items-center justify-center gap-3 overflow-x-auto pb-4">
        @foreach ($progression['exercises'] as $index => $exercise)
            @php
                $hasMinutes = $exercise['total_seconds'] > 0;
            @endphp

            <div class="flex-shrink-0 rounded-lg px-4 py-3 min-w-[160px] text-center {{ $hasMinutes ? 'bg-green-50 dark:bg-green-900/20 border-2 border-green-500 dark:border-green-600' : 'bg-gray-50 dark:bg-gray-900/20 border border-gray-300 dark:border-gray-700' }}">
                <div class="{{ $hasMinutes ? 'text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-400' }} font-medium mb-1">{{ $exercise['name'] }}</div>
                <div class="{{ $hasMinutes ? 'text-green-700 dark:text-green-400' : 'text-gray-500 dark:text-gray-500' }} text-sm font-semibold">
                    <x-duration-display :seconds="$exercise['total_seconds']" /> min
                </div>
            </div>

            @if (!$loop->last)
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-500 flex-shrink-0 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            @endif
        @endforeach
    </div>
</div>
