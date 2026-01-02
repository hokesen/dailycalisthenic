<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Activity Calendar -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Welcome, {{ auth()->user()->name }}!</h3>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Your Activity</h3>

                    <!-- Current Streak -->
                    <div class="mb-6">
                        <div class="inline-flex items-center gap-2 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg px-4 py-2">
                            <span class="text-2xl">🔥</span>
                            <div>
                                <div class="font-bold text-orange-800 dark:text-orange-400 text-xl">{{ $currentStreak }} {{ Str::plural('day', $currentStreak) }}</div>
                                <div class="text-sm text-orange-600 dark:text-orange-500">Current Streak</div>
                            </div>
                        </div>
                    </div>

                    <!-- Past Week -->
                    <div class="mb-6">
                        <h4 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-3">Past Week</h4>
                        <div class="grid grid-cols-7 gap-2">
                            @foreach ($weeklyBreakdown as $day)
                                <x-activity-day-card :day="$day" />
                            @endforeach
                        </div>
                    </div>

                    @if (count($progressionSummary) > 0)
                        <!-- Weekly Progression Summary -->
                        <div>
                            <h4 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-3">Weekly Progression Summary</h4>
                            <div class="space-y-4">
                                @foreach ($progressionSummary as $progression)
                                    <x-progression-summary-card :progression="$progression" />
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if ($userTemplates->isEmpty() && $systemTemplates->isEmpty())
                        <p class="text-gray-600 dark:text-gray-400">No workout templates available yet.</p>
                    @else
                        <div class="space-y-8">
                            @if ($userTemplates->isNotEmpty())
                                <div class="space-y-4">
                                    <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Your Templates</h4>
                                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-2">
                                        @foreach ($userTemplates as $template)
                                            <x-template-card :template="$template" :allExercises="$allExercises" />
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($systemTemplates->isNotEmpty())
                                <div class="space-y-4">
                                    <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Default Templates</h4>
                                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-2">
                                        @foreach ($systemTemplates as $template)
                                            <x-template-card :template="$template" :allExercises="$allExercises" />
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
