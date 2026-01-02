<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

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

                    <!-- Week Calendar -->
                    <div class="grid grid-cols-7 gap-2">
                        @foreach ($pastWeek as $day)
                            <div class="flex flex-col items-center">
                                <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">{{ $day['dayName'] }}</div>
                                <div class="w-12 h-12 rounded-lg border-2 flex items-center justify-center {{ $day['hasSession'] ? 'bg-green-50 dark:bg-green-900/20 border-green-500 dark:border-green-600' : 'bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-700' }}">
                                    @if ($day['hasSession'])
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $day['date']->format('j') }}</div>
                            </div>
                        @endforeach
                    </div>
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
