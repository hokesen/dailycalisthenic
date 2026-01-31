@props(['todayEntry' => null])

<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-sm mt-4">
    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Add to Today's Journal</h4>

    <form action="{{ route('journal.exercises.store', $todayEntry ?? 'new') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label for="exercise_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Exercise Name
            </label>
            <input
                type="text"
                name="name"
                id="exercise_name"
                required
                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent"
                placeholder="e.g., Push-ups, Running, Stretching"
            >
        </div>

        <div>
            <label for="duration_minutes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Duration (minutes)
            </label>
            <input
                type="number"
                name="duration_minutes"
                id="duration_minutes"
                min="1"
                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent"
                placeholder="Optional"
            >
        </div>

        <div>
            <label for="exercise_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Notes
            </label>
            <textarea
                name="notes"
                id="exercise_notes"
                rows="2"
                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:border-transparent"
                placeholder="Optional notes about this activity..."
            ></textarea>
        </div>

        <div class="flex gap-2">
            <button
                type="submit"
                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
            >
                Add Exercise
            </button>
        </div>
    </form>

    @if($todayEntry)
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Today's Exercises</h5>
            @if($todayEntry->journalExercises->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No exercises logged yet.</p>
            @else
                <div class="space-y-2">
                    @foreach($todayEntry->journalExercises as $exercise)
                        <div class="flex justify-between items-start text-sm">
                            <div>
                                <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $exercise->name }}</span>
                                @if($exercise->duration_minutes)
                                    <span class="text-gray-500 dark:text-gray-400">• {{ $exercise->duration_minutes }}m</span>
                                @endif
                                @if($exercise->notes)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $exercise->notes }}</p>
                                @endif
                            </div>
                            <form action="{{ route('journal.exercises.destroy', $exercise) }}" method="POST" class="inline">
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
        </div>
    @endif
</div>
