{{-- Meditation dashboard content - populated by meditation agent --}}
<div class="meditation-dashboard">
    @if (!$isDisciplineLive)
        <div class="app-panel sm:rounded-2xl p-6 text-center">
            <p class="text-white/60">Meditation is coming soon.</p>
        </div>
    @else
        {{-- MEDITATION_CONTENT_PLACEHOLDER --}}
        <div class="app-panel sm:rounded-2xl p-6 text-center">
            <p class="text-white/60">Meditation dashboard loading...</p>
        </div>
    @endif
</div>
