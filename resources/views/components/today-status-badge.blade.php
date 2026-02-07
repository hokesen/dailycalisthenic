@props(['hasPracticed', 'showLabel' => true])

@if ($hasPracticed)
    <div class="app-chip">
        <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        @if ($showLabel)
            <span class="hidden sm:inline">Practiced today</span>
        @endif
    </div>
@endif
