@use('Illuminate\Support\Js')
<x-app-layout>
    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-dashboard.welcome-bar
                :user="auth()->user()"
                :hasPracticed="$hasPracticedToday"
                :streak="$authUserStreak"
            />

            <!-- This Week - Progression Gantt Chart (Expanded by Default) -->
            @if (count($progressionGanttData['progressions']) > 0 || count($progressionGanttData['standalone']) > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6" x-data="{ showChart: true, ...ganttChart() }" x-init="init()">
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
                                <!-- Date Range Header -->
                                <div class="mb-3 text-center">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                        {{ $progressionGanttData['date_range']['start'] }} - {{ $progressionGanttData['date_range']['end'] }}
                                    </span>
                                </div>

                                <!-- Column Headers -->
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-28 sm:w-36"></div>
                                    <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                                        @foreach ($progressionGanttData['dayColumns'] as $index => $column)
                                            <div class="text-center {{ $column['is_today'] ? 'bg-indigo-100 dark:bg-indigo-900/30 rounded-t-sm' : '' }}">
                                                <div class="text-[10px] font-semibold {{ $column['is_today'] ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-500' }}">
                                                    {{ $column['day_name'] }}
                                                </div>
                                                <div class="text-[9px] {{ $column['is_today'] ? 'text-indigo-500 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-600' }}">
                                                    {{ $column['date'] }}
                                                </div>
                                                @if ($column['is_today'])
                                                    <div class="text-[8px] font-bold text-indigo-600 dark:text-indigo-400 uppercase">
                                                        Today
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="w-16 sm:w-20"></div>
                                </div>

                                <!-- Legend -->
                                <div class="flex items-center justify-center gap-4 mb-3 text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-4 h-4 rounded bg-emerald-500"></div>
                                        <span class="text-gray-600 dark:text-gray-400">Completed</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-4 h-4 rounded bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600"></div>
                                        <span class="text-gray-600 dark:text-gray-400">Not practiced</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-4 h-4 rounded ring-2 ring-indigo-400"></div>
                                        <span class="text-gray-600 dark:text-gray-400">Today</span>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    @foreach ($progressionGanttData['progressions'] as $progression)
                                        <!-- Progression Group Header -->
                                        <div class="flex items-center gap-2 pt-2 {{ !$loop->first ? 'mt-3 border-t border-gray-200 dark:border-gray-700' : '' }}">
                                            <div class="w-28 sm:w-36">
                                                <button
                                                    @click="toggleGroup('{{ $progression['path_name'] }}')"
                                                    class="flex items-center gap-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                                                >
                                                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': !isGroupCollapsed('{{ $progression['path_name'] }}') }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($progression['exercises']) }}</span>
                                                </button>
                                            </div>
                                            <div class="flex-1">
                                                <span class="text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wide">{{ ucwords($progression['path_name']) }}</span>
                                            </div>
                                        </div>

                                        <div x-show="!isGroupCollapsed('{{ $progression['path_name'] }}')" x-collapse>
                                        @foreach ($progression['exercises'] as $exercise)
                                            @php
                                                // Simplified binary color scheme
                                                $cellColorClass = 'bg-emerald-500 dark:bg-emerald-600';
                                                $levelIndicator = 'bg-emerald-500';
                                                // Position indicator for progression level
                                                $position = $exercise['order'];
                                                $positionDisplay = $position + 1;
                                            @endphp
                                            <div class="flex items-center gap-2">
                                                <!-- Exercise name with progression level -->
                                                <div class="w-28 sm:w-36 flex items-center gap-1.5">
                                                    <span class="flex items-center justify-center w-5 h-5 text-[10px] font-bold rounded bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 flex-shrink-0">
                                                        {{ $positionDisplay }}
                                                    </span>
                                                    <span class="text-xs text-gray-600 dark:text-gray-400 truncate" title="{{ $exercise['name'] }}">{{ $exercise['name'] }}</span>
                                                </div>
                                                <!-- Daily cells -->
                                                <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                                                    @foreach ($exercise['daily_seconds'] as $dayIndex => $seconds)
                                                        @php
                                                            $isToday = $dayIndex === $progressionGanttData['today_index'];
                                                            $todayClass = $isToday ? 'ring-2 ring-indigo-400 dark:ring-indigo-500' : '';
                                                        @endphp
                                                        <div
                                                            class="h-4 sm:h-5 rounded-sm transition-colors flex items-center justify-center {{ $seconds > 0 ? $cellColorClass : 'bg-gray-200 dark:bg-gray-700' }} {{ $todayClass }}"
                                                            title="{{ $seconds > 0 ? round($seconds / 60) . 'm' : '0m' }}"
                                                        >
                                                            @if ($seconds > 0)
                                                                <span class="text-[8px] sm:text-[10px] text-white font-medium">
                                                                    <x-duration-display :seconds="$seconds" />
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <!-- Weekly total & streak -->
                                                <div class="w-16 sm:w-20 flex items-center gap-1 justify-end">
                                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300"><x-duration-display :seconds="$exercise['weekly_seconds']" />m</span>
                                                    @if ($exercise['streak'] > 0)
                                                        <span class="flex items-center gap-0.5 text-xs text-orange-600 dark:text-orange-400" title="{{ $exercise['streak'] }} day streak">
                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                                                            </svg>
                                                            {{ $exercise['streak'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                        </div>
                                    @endforeach

                                    @if (count($progressionGanttData['standalone']) > 0)
                                        <!-- Standalone exercises section -->
                                        <div class="flex items-center gap-2 pt-2 {{ count($progressionGanttData['progressions']) > 0 ? 'mt-3 border-t border-gray-200 dark:border-gray-700' : '' }}">
                                            <div class="w-28 sm:w-36">
                                                <button
                                                    @click="toggleGroup('standalone')"
                                                    class="flex items-center gap-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                                                >
                                                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': !isGroupCollapsed('standalone') }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($progressionGanttData['standalone']) }}</span>
                                                </button>
                                            </div>
                                            <div class="flex-1">
                                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Other</span>
                                            </div>
                                        </div>

                                        <div x-show="!isGroupCollapsed('standalone')" x-collapse>
                                        @foreach ($progressionGanttData['standalone'] as $exercise)
                                            <div class="flex items-center gap-2">
                                                <div class="w-28 sm:w-36 flex items-center gap-1.5">
                                                    <div class="w-2 h-2 rounded-full flex-shrink-0 bg-emerald-500"></div>
                                                    <span class="text-xs text-gray-600 dark:text-gray-400 truncate" title="{{ $exercise['name'] }}">{{ $exercise['name'] }}</span>
                                                </div>
                                                <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                                                    @foreach ($exercise['daily_seconds'] as $dayIndex => $seconds)
                                                        @php
                                                            $isToday = $dayIndex === $progressionGanttData['today_index'];
                                                            $todayClass = $isToday ? 'ring-2 ring-indigo-400 dark:ring-indigo-500' : '';
                                                        @endphp
                                                        <div
                                                            class="h-4 sm:h-5 rounded-sm transition-colors flex items-center justify-center {{ $seconds > 0 ? 'bg-emerald-500 dark:bg-emerald-600' : 'bg-gray-200 dark:bg-gray-700' }} {{ $todayClass }}"
                                                            title="{{ $seconds > 0 ? round($seconds / 60) . 'm' : '0m' }}"
                                                        >
                                                            @if ($seconds > 0)
                                                                <span class="text-[8px] sm:text-[10px] text-white font-medium">
                                                                    <x-duration-display :seconds="$seconds" />
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <div class="w-16 sm:w-20 flex items-center gap-1 justify-end">
                                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300"><x-duration-display :seconds="$exercise['weekly_seconds']" />m</span>
                                                    @if ($exercise['streak'] > 0)
                                                        <span class="flex items-center gap-0.5 text-xs text-orange-600 dark:text-orange-400" title="{{ $exercise['streak'] }} day streak">
                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                                                            </svg>
                                                            {{ $exercise['streak'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                        </div>
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
                                        <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">Beginner</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">Intermediate</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400">Advanced</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div class="w-2 h-2 rounded-full bg-red-500"></div>
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
                            <div class="max-w-2xl">
                                <div class="mb-6">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">Get Started with Daily Calisthenics</h2>
                                    <p class="text-gray-600 dark:text-gray-400 mb-4">Choose a starter template below to begin your practice, or create your own custom template.</p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2 mb-6">
                                    @php
                                        $starterTemplates = \App\Models\SessionTemplate::whereNull('user_id')
                                            ->where('is_public', true)
                                            ->with('exercises')
                                            ->get();
                                    @endphp

                                    @foreach($starterTemplates as $starter)
                                        <a href="{{ route('home') }}?template={{ $starter->id }}" class="block p-4 bg-gray-50 dark:bg-gray-700/50 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all group">
                                            <div class="flex items-start justify-between mb-2">
                                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ $starter->name }}</h3>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $starter->exercises->count() }} exercises</span>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Click to view and copy</p>
                                        </a>
                                    @endforeach
                                </div>

                                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
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
                                            .then((template) => {
                                                window.location.href = '{{ route('home') }}?template=' + template.id;
                                            })
                                            .catch(() => {
                                                $el.disabled = false;
                                                $el.querySelector('span').textContent = 'Create Blank Template';
                                                alert('Failed to create template');
                                            });
                                        "
                                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gray-600 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 rounded-lg transition-colors disabled:opacity-50"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        <span>Create Blank Template</span>
                                    </button>
                                </div>
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
                        @php
                            $isAuthUser = $carouselData['user']->id === auth()->id();
                            $templateIds = $carouselData['templates']->pluck('id')->toArray();
                        @endphp
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg"
                             x-data="{
                                 currentIndex: {{ $isAuthUser ? ($initialTemplateIndex ?? 0) : 0 }},
                                 templateCount: {{ $carouselData['templates']->count() }},
                                 templateIds: {{ Js::from($templateIds) }},
                                 isAuthUser: {{ Js::from($isAuthUser) }},
                                 weeklyData: {{ Js::from($carouselData['weeklyBreakdown']) }},
                                 allExercises: {{ Js::from($allExercisesInWeek) }},
                                 updateUrl() {
                                     if (this.isAuthUser) {
                                         const url = new URL(window.location);
                                         url.searchParams.set('template', this.templateIds[this.currentIndex]);
                                         history.replaceState(null, '', url);
                                     }
                                 }
                             }"
                             x-init="updateUrl()"
                             x-effect="updateUrl()">
                            <div class="p-4 sm:p-6 text-gray-900 dark:text-gray-100">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $carouselData['user']->name }}
                                    </h4>
                                    <div class="flex items-center gap-2 px-2 py-1 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded">
                                        <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                                        </svg>
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
                                                    .then((template) => {
                                                        window.location.href = '{{ route('home') }}?template=' + template.id;
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
