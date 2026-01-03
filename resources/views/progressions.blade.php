<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 px-6 sm:px-0">Progressions</h3>

            @if (count($progressionSummary) > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Weekly Progression Summary</h4>
                        <div class="space-y-4">
                            @foreach ($progressionSummary as $progression)
                                <x-progression-summary-card :progression="$progression" />
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <p class="text-gray-600 dark:text-gray-400">No progressions this week. Complete some workouts to see your progress!</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
