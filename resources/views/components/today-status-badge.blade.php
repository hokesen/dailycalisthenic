@props(['hasPracticed', 'showLabel' => true])

@if ($hasPracticed)
    <div class="flex items-center gap-2 px-3 py-1.5 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        @if ($showLabel)
            <span class="text-xs font-medium text-green-700 dark:text-green-400 hidden sm:inline">Practiced today</span>
        @endif
    </div>
@endif
