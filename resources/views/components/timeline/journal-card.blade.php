@props(['entry'])

<div class="bg-white dark:bg-gray-800 border-l-4 border-green-500 rounded-lg p-4 shadow-sm">
    <div class="flex justify-between items-start mb-2">
        <div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                Journal Entry
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ \Carbon\Carbon::parse($entry->entry_date)->format('F j, Y') }}
                @if($entry->journalExercises->isNotEmpty())
                    • {{ $entry->journalExercises->sum('duration_minutes') }}m total
                @endif
            </p>
        </div>
        <span class="text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded font-medium">
            Journal
        </span>
    </div>

    @if($entry->journalExercises->isNotEmpty())
        <div class="space-y-1 mb-3">
            @foreach($entry->journalExercises as $je)
                <div class="text-sm flex justify-between items-start">
                    <div>
                        <span class="text-gray-700 dark:text-gray-300">{{ $je->name }}</span>
                        @if($je->duration_minutes)
                            <span class="text-gray-500 dark:text-gray-400">• {{ $je->duration_minutes }}m</span>
                        @endif
                        @if($je->notes)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $je->notes }}</p>
                        @endif
                    </div>
                    <form action="{{ route('journal.exercises.destroy', $je) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            onclick="return confirm('Delete this exercise?')"
                            class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 ml-2"
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
