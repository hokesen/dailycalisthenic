@use('Illuminate\Support\Js')
<x-app-layout>
    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Welcome Bar with Title, Streak, User Dropdown, and Changelog -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg mb-6" x-data="{ showChangelog: false, showUserMenu: false }">
                <div class="p-4 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <!-- Left: Title and Welcome -->
                        <div class="flex items-center gap-4">
                            <div>
                                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">Daily Calisthenic</h1>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Welcome, {{ auth()->user()->name }}!</p>
                            </div>
                        </div>

                        <!-- Right: Streak, Changelog, User Menu -->
                        <div class="flex items-center gap-3 sm:gap-4">
                            <!-- Streak -->
                            <div class="flex items-center gap-2 px-3 py-1.5 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg">
                                <span class="text-lg">🔥</span>
                                <span class="font-bold text-orange-800 dark:text-orange-400">{{ $authUserStreak }}</span>
                                <span class="text-xs text-orange-600 dark:text-orange-500 hidden sm:inline">day streak</span>
                            </div>

                            <!-- Changelog Toggle -->
                            <button @click="showChangelog = !showChangelog" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium hidden sm:block">
                                <span x-text="showChangelog ? 'Hide Changelog' : 'Show Changelog'"></span>
                            </button>

                            <!-- User Dropdown -->
                            <div class="relative" @click.outside="showUserMenu = false">
                                <button @click="showUserMenu = !showUserMenu" class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 bg-gray-100 dark:bg-gray-700 rounded-lg transition-colors">
                                    <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                                    <span class="sm:hidden">Menu</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="showUserMenu" x-transition x-cloak class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50">
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-t-lg">Profile</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-b-lg">Log Out</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Changelog (Hidden by default) -->
                    <div x-show="showChangelog" x-transition class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Changelog</h4>
                        <div class="space-y-3">
                            <div class="border-l-4 border-green-500 pl-4">
                                <div class="flex items-baseline gap-2 mb-1">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">January 11th, 2026</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Improvements & Features</span>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">1. Redesigned to single-page mobile-friendly layout<br> 2. Added gantt chart for exercise history<br>3. Added template privacy setting</p>
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

            <!-- This Week - Progression Gantt Chart (Expanded by Default) -->
            @if (count($progressionGanttData['progressions']) > 0 || count($progressionGanttData['standalone']) > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6" x-data="{ showChart: true }">
                    <div class="p-4 sm:p-6">
                        <button @click="showChart = !showChart" class="w-full flex items-center justify-between text-left">
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Activity</h3>
                            <div class="flex items-center gap-2">
                                @php
                                    $totalExercises = array_sum(array_map(fn($p) => count($p['exercises']), $progressionGanttData['progressions'])) + count($progressionGanttData['standalone']);
                                @endphp
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $totalExercises }} exercises</span>
                                <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'rotate-180': showChart }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </button>

                        <div x-show="showChart" x-transition class="mt-4">
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 sm:p-4 border border-gray-200 dark:border-gray-700">
                                <div class="space-y-1">
                                    @foreach ($progressionGanttData['progressions'] as $progression)
                                        <!-- Progression Group Header -->
                                        <div class="flex items-center gap-2 pt-2 {{ !$loop->first ? 'mt-3 border-t border-gray-200 dark:border-gray-700' : '' }}">
                                            <div class="w-28 sm:w-36"></div>
                                            <div class="flex-1">
                                                <span class="text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wide">{{ ucwords($progression['path_name']) }}</span>
                                            </div>
                                        </div>
                                        @foreach ($progression['exercises'] as $exercise)
                                            @php
                                                // Color coding based on position in progression (0 = easiest)
                                                $progressLevel = $exercise['order'] / max(1, $exercise['total_in_path'] - 1);
                                                if ($exercise['total_in_path'] <= 1) {
                                                    $cellColorClass = 'bg-green-500 dark:bg-green-600';
                                                    $levelIndicator = 'bg-green-500';
                                                } elseif ($progressLevel >= 0.8) {
                                                    $cellColorClass = 'bg-emerald-500 dark:bg-emerald-600';
                                                    $levelIndicator = 'bg-emerald-500';
                                                } elseif ($progressLevel >= 0.5) {
                                                    $cellColorClass = 'bg-green-500 dark:bg-green-600';
                                                    $levelIndicator = 'bg-green-500';
                                                } elseif ($progressLevel >= 0.25) {
                                                    $cellColorClass = 'bg-yellow-500 dark:bg-yellow-600';
                                                    $levelIndicator = 'bg-yellow-500';
                                                } else {
                                                    $cellColorClass = 'bg-orange-500 dark:bg-orange-600';
                                                    $levelIndicator = 'bg-orange-500';
                                                }
                                                $weeklyMinutes = round($exercise['weekly_seconds'] / 60);
                                            @endphp
                                            <div class="flex items-center gap-2">
                                                <!-- Exercise name with level indicator -->
                                                <div class="w-28 sm:w-36 flex items-center gap-1.5">
                                                    <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $levelIndicator }}"></div>
                                                    <span class="text-xs text-gray-600 dark:text-gray-400 truncate" title="{{ $exercise['name'] }}">{{ $exercise['name'] }}</span>
                                                </div>
                                                <!-- Daily cells -->
                                                <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                                                    @foreach ($exercise['daily_seconds'] as $dayIndex => $seconds)
                                                        @php
                                                            $dailyMinutes = round($seconds / 60);
                                                        @endphp
                                                        <div
                                                            class="h-4 sm:h-5 rounded-sm transition-colors flex items-center justify-center {{ $seconds > 0 ? $cellColorClass : 'bg-gray-200 dark:bg-gray-700' }}"
                                                            title="{{ $dailyMinutes }} min"
                                                        >
                                                            @if ($seconds > 0)
                                                                <span class="text-[8px] sm:text-[10px] text-white font-medium">{{ $dailyMinutes }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <!-- Weekly total & streak -->
                                                <div class="w-16 sm:w-20 flex items-center gap-1 justify-end">
                                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $weeklyMinutes }}m</span>
                                                    @if ($exercise['streak'] > 0)
                                                        <span class="text-xs text-orange-600 dark:text-orange-400" title="{{ $exercise['streak'] }} day streak">🔥{{ $exercise['streak'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    @endforeach

                                    @if (count($progressionGanttData['standalone']) > 0)
                                        <!-- Standalone exercises section -->
                                        <div class="flex items-center gap-2 pt-2 {{ count($progressionGanttData['progressions']) > 0 ? 'mt-3 border-t border-gray-200 dark:border-gray-700' : '' }}">
                                            <div class="w-28 sm:w-36"></div>
                                            <div class="flex-1">
                                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Other</span>
                                            </div>
                                        </div>
                                        @foreach ($progressionGanttData['standalone'] as $exercise)
                                            @php
                                                $weeklyMinutes = round($exercise['weekly_seconds'] / 60);
                                            @endphp
                                            <div class="flex items-center gap-2">
                                                <div class="w-28 sm:w-36 flex items-center gap-1.5">
                                                    <div class="w-2 h-2 rounded-full flex-shrink-0 bg-blue-500"></div>
                                                    <span class="text-xs text-gray-600 dark:text-gray-400 truncate" title="{{ $exercise['name'] }}">{{ $exercise['name'] }}</span>
                                                </div>
                                                <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                                                    @foreach ($exercise['daily_seconds'] as $dayIndex => $seconds)
                                                        @php
                                                            $dailyMinutes = round($seconds / 60);
                                                        @endphp
                                                        <div
                                                            class="h-4 sm:h-5 rounded-sm transition-colors flex items-center justify-center {{ $seconds > 0 ? 'bg-blue-500 dark:bg-blue-600' : 'bg-gray-200 dark:bg-gray-700' }}"
                                                            title="{{ $dailyMinutes }} min"
                                                        >
                                                            @if ($seconds > 0)
                                                                <span class="text-[8px] sm:text-[10px] text-white font-medium">{{ $dailyMinutes }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <div class="w-16 sm:w-20 flex items-center gap-1 justify-end">
                                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $weeklyMinutes }}m</span>
                                                    @if ($exercise['streak'] > 0)
                                                        <span class="text-xs text-orange-600 dark:text-orange-400" title="{{ $exercise['streak'] }} day streak">🔥{{ $exercise['streak'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <!-- Day labels -->
                                <div class="flex items-center gap-2 mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <div class="w-28 sm:w-36"></div>
                                    <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                                        @foreach ($progressionGanttData['dayLabels'] as $dayLabel)
                                            <div class="text-[10px] text-gray-500 dark:text-gray-400 text-center">{{ $dayLabel }}</div>
                                        @endforeach
                                    </div>
                                    <div class="w-16 sm:w-20"></div>
                                </div>

                                <!-- Daily & Weekly Totals -->
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="w-28 sm:w-36 text-xs font-medium text-gray-600 dark:text-gray-400 text-right pr-1">Total</div>
                                    <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                                        @foreach ($progressionGanttData['dailyTotals'] as $dailyTotal)
                                            @php
                                                $dailyMinutes = round($dailyTotal / 60);
                                            @endphp
                                            <div class="text-[10px] sm:text-xs font-semibold text-center {{ $dailyTotal > 0 ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-600' }}">
                                                {{ $dailyMinutes > 0 ? $dailyMinutes : '-' }}
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="w-16 sm:w-20 flex items-center justify-end">
                                        @php
                                            $weeklyMinutes = round($progressionGanttData['weeklyTotal'] / 60);
                                        @endphp
                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ $weeklyMinutes }}m</span>
                                    </div>
                                </div>

                                <!-- Legend -->
                                <div class="flex flex-wrap items-center gap-3 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Level:</span>
                                    <div class="flex items-center gap-1">
                                        <div class="w-2 h-2 rounded-full bg-orange-500"></div>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">Beginner</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">Intermediate</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">Advanced</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">Expert</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($userCarouselData->isEmpty())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg" x-data="{ created: false, cardHtml: '' }">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <template x-if="!created">
                            <div>
                                <p class="text-gray-600 dark:text-gray-400 mb-4">No templates yet. Create one to get started!</p>
                                <button
                                    @click="
                                        $el.disabled = true;
                                        $el.querySelector('span').textContent = 'Creating...';
                                        fetch('{{ route('templates.store') }}', {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Accept': 'application/json'
                                            }
                                        })
                                        .then(r => {
                                            if (!r.ok) throw new Error('Failed to create template');
                                            return r.json();
                                        })
                                        .then(() => {
                                            window.location.reload();
                                        })
                                        .catch(() => {
                                            $el.disabled = false;
                                            $el.querySelector('span').textContent = 'Create Your First Template';
                                            alert('Failed to create template');
                                        });
                                    "
                                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors disabled:opacity-50"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Create Your First Template</span>
                                </button>
                            </div>
                        </template>
                        <template x-if="created">
                            <div x-html="cardHtml"></div>
                        </template>
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
                                 weeklyData: {{ Js::from($carouselData['weeklyBreakdown']) }},
                                 allExercises: {{ Js::from($allExercisesInWeek) }}
                             }">
                            <div class="p-4 sm:p-6 text-gray-900 dark:text-gray-100">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $carouselData['user']->name }}
                                    </h4>
                                    <div class="flex items-center gap-2 px-2 py-1 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded">
                                        <span>🔥</span>
                                        <span class="font-bold text-orange-800 dark:text-orange-400 text-sm">{{ $carouselData['currentStreak'] }}</span>
                                    </div>
                                </div>

                                <!-- Gantt Chart for Exercises (Only for other users, auth user has it in the combined section above) -->
                                @if ($carouselData['user']->id !== auth()->id())
                                    <template x-if="allExercises.length > 0">
                                        <div class="mb-6">
                                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 sm:p-4 border border-gray-200 dark:border-gray-700">
                                                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">This Week</div>
                                                <div class="space-y-2">
                                                    <template x-for="(exercise, exIndex) in allExercises" :key="exIndex">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-20 sm:w-24 text-xs text-gray-600 dark:text-gray-400 truncate" x-text="exercise"></div>
                                                            <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                                                                <template x-for="(day, dayIdx) in weeklyData" :key="dayIdx">
                                                                    <div
                                                                        class="h-3 sm:h-4 rounded-sm transition-colors"
                                                                        :class="{
                                                                            'bg-green-500 dark:bg-green-600': day.exercises.some(e => e.name === exercise),
                                                                            'bg-gray-200 dark:bg-gray-700': !day.exercises.some(e => e.name === exercise)
                                                                        }"
                                                                    ></div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                                <!-- Day labels -->
                                                <div class="flex items-center gap-2 mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                                    <div class="w-20 sm:w-24"></div>
                                                    <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                                                        @foreach ($carouselData['weeklyBreakdown'] as $day)
                                                            <div class="text-[10px] text-gray-500 dark:text-gray-400 text-center">{{ substr($day['dayName'], 0, 1) }}</div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                @endif

                                <!-- Template Carousel -->
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-3">
                                        <!-- New Template button (only for auth user) -->
                                        @if ($carouselData['user']->id === auth()->id())
                                            <button
                                                @click="
                                                    $el.disabled = true;
                                                    $el.querySelector('span').textContent = 'Creating...';
                                                    fetch('{{ route('templates.store') }}', {
                                                        method: 'POST',
                                                        headers: {
                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                            'Accept': 'application/json'
                                                        }
                                                    })
                                                    .then(r => {
                                                        if (!r.ok) throw new Error('Failed to create template');
                                                        return r.json();
                                                    })
                                                    .then(() => {
                                                        window.location.reload();
                                                    })
                                                    .catch((e) => {
                                                        console.error(e);
                                                        $el.disabled = false;
                                                        $el.querySelector('span').textContent = 'New Template';
                                                        alert('Failed to create template');
                                                    });
                                                "
                                                class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors disabled:opacity-50"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                </svg>
                                                <span>New Template</span>
                                            </button>
                                        @else
                                            <div></div>
                                        @endif

                                        <!-- Carousel controls -->
                                        @if ($carouselData['templates']->count() > 1)
                                            <div class="flex items-center gap-2">
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
                                    </div>

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
