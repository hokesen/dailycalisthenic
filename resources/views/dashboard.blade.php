@use('Illuminate\Support\Js')
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Welcome & Changelog Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6" x-data="{ showChangelog: false }">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Welcome, {{ auth()->user()->name }}!</h3>
                        <button @click="showChangelog = !showChangelog" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
                            <span x-text="showChangelog ? 'Hide Changelog' : 'Show Changelog'"></span>
                        </button>
                    </div>

                    <!-- Changelog -->
                    <div x-show="showChangelog" x-transition class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Changelog</h4>
                        <div class="space-y-3">
                            <div class="border-l-4 border-green-500 pl-4">
                                <div class="flex items-baseline gap-2 mb-1">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">January 11th, 2026</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Improvements & Features</span>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">1. Added gantt chart for exercise history<br> 2. Improved design on mobile<br> 3. You can now set your template to be shown publicly or kept private.</p>
                            </div>
                            <div class="border-l-4 border-blue-500 pl-4">
                                <div class="flex items-baseline gap-2 mb-1">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">January 4th, 2026</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">New Features</span>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">1. Exercises can now be re-ordered with arrow buttons<br> 2. Go page is now full screen for better visibility<br> 3. Activity page shows everything you've done in the past week/month.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($userCarouselData->isEmpty())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <p class="text-gray-600 dark:text-gray-400">No recent activity to display. Start a workout to see your progress!</p>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach ($userCarouselData as $carouselData)
                        @php
                            // Collect all unique exercises across the week for Gantt chart
                            $allExercisesInWeek = collect($carouselData['weeklyBreakdown'])
                                ->flatMap(fn($day) => collect($day['exercises'])->pluck('name'))
                                ->unique()
                                ->values()
                                ->toArray();
                        @endphp
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
                             x-data="{
                                 currentIndex: 0,
                                 templateCount: {{ $carouselData['templates']->count() }},
                                 selectedDay: null,
                                 weeklyData: {{ Js::from($carouselData['weeklyBreakdown']) }},
                                 allExercises: {{ Js::from($allExercisesInWeek) }},
                                 toggleDay(dayIndex) {
                                     this.selectedDay = this.selectedDay === dayIndex ? null : dayIndex;
                                 }
                             }">
                            <div class="p-6 text-gray-900 dark:text-gray-100">
                                <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-6">
                                    {{ $carouselData['user']->name }}
                                </h4>

                                <!-- Week Activity Grid with Streak -->
                                <div class="mb-2">
                                    <div class="grid grid-cols-8 gap-2">
                                        @foreach ($carouselData['weeklyBreakdown'] as $index => $day)
                                            <x-activity-day-card :day="$day" :dayIndex="$index" />
                                        @endforeach
                                        <!-- Streak Card -->
                                        <div class="flex flex-col items-center justify-center p-2 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg">
                                            <span class="text-2xl mb-1">🔥</span>
                                            <div class="font-bold text-orange-800 dark:text-orange-400 text-sm">{{ $carouselData['currentStreak'] }}</div>
                                            <div class="text-xs text-orange-600 dark:text-orange-500 text-center">Streak</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gantt Chart for Exercises -->
                                <div x-show="selectedDay !== null" x-transition x-cloak class="mb-6">
                                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Exercise History</div>
                                        <template x-if="allExercises.length === 0">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">No exercises this week</div>
                                        </template>
                                        <div class="space-y-2">
                                            <template x-for="(exercise, exIndex) in allExercises" :key="exIndex">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-24 text-xs text-gray-600 dark:text-gray-400 truncate" x-text="exercise"></div>
                                                    <div class="flex-1 grid grid-cols-7 gap-1">
                                                        <template x-for="(day, dayIdx) in weeklyData" :key="dayIdx">
                                                            <div
                                                                class="h-4 rounded-sm transition-colors"
                                                                :class="{
                                                                    'bg-green-500 dark:bg-green-600': day.exercises.some(e => e.name === exercise),
                                                                    'bg-gray-200 dark:bg-gray-700': !day.exercises.some(e => e.name === exercise),
                                                                    'ring-2 ring-blue-500 ring-offset-1 dark:ring-offset-gray-900': selectedDay === dayIdx
                                                                }"
                                                            ></div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <!-- Day labels -->
                                        <div class="flex items-center gap-2 mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                            <div class="w-24"></div>
                                            <div class="flex-1 grid grid-cols-7 gap-1">
                                                @foreach ($carouselData['weeklyBreakdown'] as $day)
                                                    <div class="text-[10px] text-gray-500 dark:text-gray-400 text-center">{{ substr($day['dayName'], 0, 1) }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Template Carousel -->
                                <div>
                                    @if ($carouselData['templates']->count() > 1)
                                        <div class="flex items-center justify-end gap-2 mb-3">
                                            <button @click="currentIndex = (currentIndex - 1 + templateCount) % templateCount"
                                                    class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                </svg>
                                            </button>
                                            <span class="text-sm text-gray-600 dark:text-gray-400" x-text="`${currentIndex + 1} / ${templateCount}`"></span>
                                            <button @click="currentIndex = (currentIndex + 1) % templateCount"
                                                    class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    @endif

                                    @foreach ($carouselData['templates'] as $index => $template)
                                        <div x-show="currentIndex === {{ $index }}" x-transition>
                                            <x-template-card :template="$template" :allExercises="$allExercises" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
