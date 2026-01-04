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
                            <div class="border-l-4 border-blue-500 pl-4">
                                <div class="flex items-baseline gap-2 mb-1">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">January 4th, 2026</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">New Feature</span>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">You can now re-order exercises in your templates using the up and down arrow buttons!</p>
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
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
                             x-data="{ currentIndex: 0, templateCount: {{ $carouselData['templates']->count() }} }">
                            <div class="p-6 text-gray-900 dark:text-gray-100">
                                <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-6">
                                    {{ $carouselData['user']->name }}
                                </h4>

                                <!-- Week Activity Grid with Streak -->
                                <div class="mb-6">
                                    <div class="grid grid-cols-8 gap-2">
                                        @foreach ($carouselData['weeklyBreakdown'] as $day)
                                            <x-activity-day-card :day="$day" />
                                        @endforeach
                                        <!-- Streak Card -->
                                        <div class="flex flex-col items-center justify-center p-2 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg">
                                            <span class="text-2xl mb-1">🔥</span>
                                            <div class="font-bold text-orange-800 dark:text-orange-400 text-sm">{{ $carouselData['currentStreak'] }}</div>
                                            <div class="text-xs text-orange-600 dark:text-orange-500 text-center">Streak</div>
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
                                        @php
                                            $isCurrentUser = $carouselData['user']->id === auth()->id();
                                            $isTopTemplate = $template->id === $carouselData['topTemplateId'];
                                            $borderClass = ($isTopTemplate && $isCurrentUser) ? 'ring-2 ring-yellow-400 dark:ring-yellow-500 rounded-lg p-3' : '';
                                        @endphp
                                        <div x-show="currentIndex === {{ $index }}" x-transition class="{{ $borderClass }}">
                                            <h5 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                                <!--Template-->
                                                @if ($isTopTemplate && $isCurrentUser)
                                                    <span class="text-xs font-normal text-yellow-600 dark:text-yellow-400">(Top - Publicly Visible)</span>
                                                @endif
                                            </h5>
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
