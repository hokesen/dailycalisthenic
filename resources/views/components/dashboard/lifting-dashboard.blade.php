{{-- Lifting dashboard content - populated by lifting agent --}}
<div class="lifting-dashboard">
    @if (!$isDisciplineLive)
        <div class="app-panel sm:rounded-2xl p-6 text-center">
            <p class="text-white/60">Lifting is coming soon.</p>
        </div>
    @else
        {{-- LIFTING_CONTENT_PLACEHOLDER --}}
        <div class="app-panel sm:rounded-2xl p-6 text-center">
            <p class="text-white/60">Lifting dashboard loading...</p>
        </div>
    @endif
</div>
