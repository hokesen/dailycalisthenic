@props(['journalEntries', 'userNow'])

<section class="app-panel rounded-2xl">
    <div class="p-6">
        <h3 class="app-section-title mb-4">Journal Entries</h3>

        @if ($journalEntries->isEmpty())
            <div class="rounded-xl border border-dashed border-white/15 bg-white/5 px-4 py-8 text-center">
                <h4 class="text-lg font-semibold text-white">No journal notes yet</h4>
                <p class="mt-2 text-sm text-white/60">Submit your first entry above and it will show up here.</p>
            </div>
        @else
            <div class="divide-y divide-white/10">
                @foreach ($journalEntries as $entry)
                    @php
                        $normalizedNote = trim(preg_replace('/\s+/', ' ', (string) $entry->notes));
                        $isToday = $entry->entry_date?->isSameDay($userNow);
                    @endphp
                    <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:gap-4">
                        <div class="flex items-center gap-2 shrink-0 sm:w-44">
                            <span class="text-sm font-semibold text-white/80">{{ $entry->entry_date?->format('D, M j') }}</span>
                            @if ($isToday)
                                <span class="app-chip">Today</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1 text-sm text-white/65 truncate">
                            {{ $normalizedNote }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
