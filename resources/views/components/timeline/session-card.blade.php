@props(['session'])

<div class="app-card rounded-xl p-4 border-l-4 border-emerald-400">
    <div class="flex justify-between items-start mb-2">
        <div>
            <h4 class="font-semibold text-white">
                {{ $session->name }}
            </h4>
            <p class="text-sm text-white/60">
                {{ $session->completed_at->format('g:i A') }} • <x-duration-display :seconds="$session->total_duration_seconds" /> total
            </p>
        </div>
        <span class="app-chip">
            Session
        </span>
    </div>

    @if($session->sessionExercises->isNotEmpty())
        <div class="space-y-1 mb-3">
            @foreach($session->sessionExercises as $se)
                <div class="text-sm">
                    <span class="text-white/80">{{ $se->exercise->name }}</span>
                    @if($se->duration_seconds)
                        <span class="text-white/50">• <x-duration-display :seconds="$se->duration_seconds" /></span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <x-timeline.inline-notes
        :model="$session"
        :notes="$session->notes"
        updateRoute="sessions.update-notes"
    />
</div>
