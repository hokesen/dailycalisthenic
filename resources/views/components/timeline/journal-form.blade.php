@props(['todayEntry' => null])

<div class="app-panel rounded-2xl p-6 mt-4">
    <h4 class="font-semibold text-white mb-4">Add to Today's Journal</h4>

    <form action="{{ route('journal.exercises.store', $todayEntry ?? 'new') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="exercise_name" :value="__('Exercise Name')" />
            <input
                type="text"
                name="name"
                id="exercise_name"
                required
                class="app-input px-3 py-2.5 text-sm sm:text-base"
                placeholder="e.g., Push-ups, Running, Stretching"
            >
        </div>

        <div>
            <x-input-label for="duration_minutes" :value="__('Duration (minutes)')" />
            <input
                type="number"
                name="duration_minutes"
                id="duration_minutes"
                min="1"
                class="app-input px-3 py-2.5 text-sm sm:text-base"
                placeholder="Optional"
            >
        </div>

        <div>
            <x-input-label for="exercise_notes" :value="__('Notes')" />
            <textarea
                name="notes"
                id="exercise_notes"
                rows="2"
                class="app-input px-3 py-2.5 text-sm sm:text-base"
                placeholder="Optional notes about this activity..."
            ></textarea>
        </div>

        <div class="flex gap-2">
            <button
                type="submit"
                class="app-btn app-btn-primary"
            >
                Add Exercise
            </button>
        </div>
    </form>

    @if($todayEntry)
        <div class="mt-6 pt-6 border-t border-white/10">
            <h5 class="text-sm font-semibold text-white/70 mb-3">Today's Exercises</h5>
            @if($todayEntry->journalExercises->isEmpty())
                <p class="text-sm text-white/50 italic">No exercises logged yet.</p>
            @else
                <div class="space-y-2">
                    @foreach($todayEntry->journalExercises as $exercise)
                        <div class="flex justify-between items-start text-sm">
                            <div>
                                <span class="text-white/80 font-medium">{{ $exercise->name }}</span>
                                @if($exercise->duration_minutes)
                                    <span class="text-white/50">• {{ $exercise->duration_minutes }}m</span>
                                @endif
                                @if($exercise->notes)
                                    <p class="text-xs text-white/50 mt-1">{{ $exercise->notes }}</p>
                                @endif
                            </div>
                            <form action="{{ route('journal.exercises.destroy', $exercise) }}" method="POST" class="inline">
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
        </div>
    @endif
</div>
