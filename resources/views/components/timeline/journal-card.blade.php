@props(['entry'])

<div class="app-card rounded-xl p-4 border-l-4 border-cyan-400">
    <div class="flex justify-between items-start mb-2">
        <div>
            <h4 class="font-semibold text-white">
                Journal Entry
            </h4>
            <p class="text-sm text-white/60">
                {{ \Carbon\Carbon::parse($entry->entry_date)->format('F j, Y') }}
                @if($entry->journalExercises->isNotEmpty())
                    • {{ $entry->journalExercises->sum('duration_minutes') }}m total
                @endif
            </p>
        </div>
        <span class="app-chip app-chip--warm">
            Journal
        </span>
    </div>

    @if($entry->journalExercises->isNotEmpty())
        <div class="space-y-1 mb-3">
            @foreach($entry->journalExercises as $je)
                <div class="text-sm flex justify-between items-start">
                    <div>
                        <span class="text-white/80">{{ $je->name }}</span>
                        @if($je->duration_minutes)
                            <span class="text-white/50">• {{ $je->duration_minutes }}m</span>
                        @endif
                        @if($je->notes)
                            <p class="text-xs text-white/50 mt-1">{{ $je->notes }}</p>
                        @endif
                    </div>
                    <form action="{{ route('journal.exercises.destroy', $je) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            onclick="return confirm('Delete this exercise?')"
                            class="text-white/40 hover:text-red-300 ml-2"
                            title="Delete exercise"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    <x-timeline.inline-notes
        :model="$entry"
        :notes="$entry->notes"
        updateRoute="journal.update"
    />
</div>
