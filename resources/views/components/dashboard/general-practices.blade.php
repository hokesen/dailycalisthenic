@props(['leaderboardEntries'])

<section class="app-panel rounded-2xl">
    <div class="p-6">
        <div class="space-y-2">
            @foreach ($leaderboardEntries as $entry)
                <div class="flex items-center justify-between gap-4 rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-white">
                            {{ $entry['user']->name }}
                            @if ($entry['is_current_user'])
                                <span class="text-white/45">(You)</span>
                            @endif
                        </div>
                    </div>
                    <div class="shrink-0 text-sm font-semibold text-emerald-200">
                        {{ $entry['streak'] }} day{{ $entry['streak'] === 1 ? '' : 's' }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
