<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6 px-6 sm:px-0">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Activity</h3>

                <form method="GET" action="{{ route('activity') }}" class="flex items-center gap-2">
                    <label for="range" class="text-sm font-medium text-gray-700 dark:text-gray-300">Show:</label>
                    <select
                        name="range"
                        id="range"
                        onchange="this.form.submit()"
                        class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-sm"
                    >
                        <option value="week" {{ $selectedRange === 'week' ? 'selected' : '' }}>Past Week</option>
                        <option value="month" {{ $selectedRange === 'month' ? 'selected' : '' }}>Past Month</option>
                    </select>
                </form>
            </div>

            @if (count($progressionSummary) > 0 || count($standaloneExercises) > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Your Activity</h4>

                        @if (count($progressionSummary) > 0)
                            <div class="space-y-4 mb-6">
                                @foreach ($progressionSummary as $progression)
                                    <x-progression-summary-card :progression="$progression" />
                                @endforeach
                            </div>
                        @endif

                        @if (count($standaloneExercises) > 0)
                            <div class="flex flex-wrap gap-3 {{ count($progressionSummary) > 0 ? 'pt-6 border-t border-gray-200 dark:border-gray-700' : '' }}">
                                @foreach ($standaloneExercises as $exercise)
                                    @php
                                        $minutes = round($exercise['total_seconds'] / 60);
                                    @endphp
                                    <div class="flex-shrink-0 rounded-lg px-4 py-3 min-w-[160px] text-center bg-green-50 dark:bg-green-900/20 border-2 border-green-500 dark:border-green-600">
                                        <div class="text-gray-900 dark:text-gray-100 font-medium mb-1">{{ $exercise['name'] }}</div>
                                        <div class="text-green-700 dark:text-green-400 text-sm font-semibold">{{ $minutes }} min</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <p class="text-gray-600 dark:text-gray-400">No activity in this time period. Complete some workouts to see your progress!</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
