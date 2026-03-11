@use('Illuminate\Support\Js')
<x-app-layout>
    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-dashboard.welcome-bar
                :user="auth()->user()"
                :hasPracticed="$hasPracticedToday"
                :streak="$authUserStreak"
                :disciplines="$disciplines"
                :selectedDiscipline="$selectedDiscipline"
            />

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-400/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-2xl border border-rose-400/25 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Tab Navigation -->
            <div class="mb-6 app-reveal" x-data="{
                activeTab: {{ Js::from(request()->input('tab', 'timeline')) }},
                updateUrl(tab) {
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tab);
                    history.replaceState(null, '', url);
                },
                goToDays(dayCount, tab = this.activeTab) {
                    const url = new URL(window.location);
                    url.searchParams.set('days', dayCount);
                    url.searchParams.set('tab', tab);
                    window.location.href = url.toString();
                }
            }" @keydown.arrow-right.window="
                if (activeTab === 'timeline') activeTab = 'progress';
                else if (activeTab === 'progress') activeTab = 'templates';
            " @keydown.arrow-left.window="
                if (activeTab === 'templates') activeTab = 'progress';
                else if (activeTab === 'progress') activeTab = 'timeline';
            ">
                <div class="app-panel rounded-2xl px-4 sm:px-6">
                    <nav class="-mb-px flex gap-4 sm:gap-8 overflow-x-auto" aria-label="Tabs" role="tablist">
                        <button
                            @click="activeTab = 'timeline'; updateUrl('timeline')"
                            :class="activeTab === 'timeline' ? 'app-tab active' : 'app-tab'"
                            :aria-selected="activeTab === 'timeline'"
                            role="tab"
                            aria-controls="timeline-panel"
                            class="whitespace-nowrap py-4 px-1 border-b-2 border-transparent text-sm transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-transparent"
                        >
                            Timeline
                        </button>
                        <button
                            @click="activeTab = 'progress'; updateUrl('progress')"
                            :class="activeTab === 'progress' ? 'app-tab active' : 'app-tab'"
                            :aria-selected="activeTab === 'progress'"
                            role="tab"
                            aria-controls="progress-panel"
                            class="whitespace-nowrap py-4 px-1 border-b-2 border-transparent text-sm transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-transparent"
                        >
                            {{ $selectedDiscipline === 'soccer' ? 'Progress & Benchmarks' : 'Goals' }}
                        </button>
                        <button
                            @click="activeTab = 'templates'; updateUrl('templates')"
                            :class="activeTab === 'templates' ? 'app-tab active' : 'app-tab'"
                            :aria-selected="activeTab === 'templates'"
                            role="tab"
                            aria-controls="templates-panel"
                            class="whitespace-nowrap py-4 px-1 border-b-2 border-transparent text-sm transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-transparent"
                        >
                            Practices
                        </button>
                    </nav>
                </div>

                <!-- Timeline Tab Content -->
                <div x-show="activeTab === 'timeline'" x-transition class="mt-6" role="tabpanel" id="timeline-panel" aria-labelledby="timeline-tab">
                    <!-- Quick Actions -->
                    <x-timeline.quick-actions :templates="$userTemplates" :todayEntry="$todayEntry" />

                    <!-- Lightweight Recent History -->
                    <x-timeline.recent-history :recentHistory="$recentHistory" />

                    <!-- Filter Controls -->
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="app-section-title">Practice Log</h3>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-white/60">Range:</span>
                            <div class="inline-flex rounded-full border border-white/10 bg-black/20 p-1">
                                @foreach ([7, 14, 30, 90] as $range)
                                    <button
                                        type="button"
                                        @click="goToDays({{ $range }}, 'timeline')"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-full transition-colors {{ $days === $range ? 'bg-emerald-500/30 text-emerald-200' : 'text-white/60 hover:text-white hover:bg-white/10' }}"
                                    >
                                        {{ $range }}d
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Feed -->

                    <x-timeline.feed :timelineFeed="$timelineFeed" :timezone="$userTimezone" :userNow="$userNow" />
                </div>

                <!-- Goals Tab Content -->
                <div x-show="activeTab === 'progress'" x-transition class="mt-6" role="tabpanel" id="progress-panel" aria-labelledby="progress-tab">
                    @if ($selectedDiscipline === 'general')
                    <!-- Goals Selection -->
                    <div class="app-panel sm:rounded-2xl mb-6" x-data="{
                            showGoalSelection: false,
                            selectedExercises: {{ json_encode($currentUserGoal?->exercise_goals ?? []) }},
                            saving: false,
                            saveGoals() {
                                this.saving = true;
                                fetch('{{ route('user-goals.update-exercise-goals') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        _method: 'PATCH',
                                        exercise_ids: this.selectedExercises
                                    })
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    this.saving = false;
                                    if (data.success) {
                                        window.location.reload();
                                    }
                                })
                                .catch(error => {
                                    console.error(error);
                                    this.saving = false;
                                    alert('Failed to save goals');
                                });
                            },
                            toggleExercise(exerciseId) {
                                const index = this.selectedExercises.indexOf(exerciseId);
                                if (index > -1) {
                                    this.selectedExercises.splice(index, 1);
                                } else {
                                    this.selectedExercises.push(exerciseId);
                                }
                            },
                            isSelected(exerciseId) {
                                return this.selectedExercises.includes(exerciseId);
                            }
                        }">
                        <div class="p-4 sm:p-6">
                            <div class="flex items-center justify-between">
                                <button @click="showGoalSelection = !showGoalSelection" class="flex items-center gap-2">
                                    <h3 class="app-section-title">Select Your Goals</h3>
                                    <svg class="w-5 h-5 text-white/60 transition-transform" :class="{ 'rotate-180': showGoalSelection }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                            </div>

                            <div x-show="showGoalSelection" x-transition class="mt-4">
                                <p class="text-sm text-white/60 mb-4">
                                    Choose which exercises you want to focus on. Your goals chart below will show only these exercises.
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach ($allExercises->groupBy('category') as $category => $exercises)
                                    <div class="col-span-full">
                                        <h4 class="text-sm font-semibold text-white/70 mt-4 mb-2">{{ ucfirst($category) }}</h4>
                                    </div>
                                    @foreach ($exercises as $exercise)
                                        <label class="flex items-center gap-2 p-3 app-card rounded-lg cursor-pointer">
                                            <input
                                                type="checkbox"
                                                :checked="isSelected({{ $exercise->id }})"
                                                @change="toggleExercise({{ $exercise->id }})"
                                                class="w-4 h-4 text-emerald-400 bg-transparent border-white/20 rounded focus:ring-emerald-400 focus:ring-2"
                                            >
                                            <span class="text-sm text-white/80">{{ $exercise->name }}</span>
                                        </label>
                                    @endforeach
                                @endforeach
                                </div>
                                <div class="flex justify-end pt-4">
                                    <button
                                        @click="saveGoals()"
                                        :disabled="saving"
                                        class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
                                    >
                                        <span x-show="!saving">Save Goals</span>
                                        <span x-show="saving">Saving...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Goals Chart -->
                    @if (count($progressionGanttData['progressions']) > 0 || count($progressionGanttData['standalone']) > 0)
                @php
                    $hasGoals = $currentUserGoal && !empty($currentUserGoal->exercise_goals);
                    $goalExerciseIds = $hasGoals ? $currentUserGoal->exercise_goals : [];
                @endphp
                <div class="app-panel sm:rounded-2xl mb-6" x-data="{
                    showOnlyGoals: {{ $hasGoals ? 'true' : 'false' }},
                    goalExerciseIds: {{ json_encode($goalExerciseIds) }},
                    isGoalExercise(exerciseId) {
                        return this.goalExerciseIds.includes(exerciseId);
                    },
                    shouldShowExerciseInGoalMode(exerciseId) {
                        return !this.showOnlyGoals || this.isGoalExercise(exerciseId);
                    },
                    ...ganttChart()
                }" x-init="init()">
                    <div class="p-4 sm:p-6">
                        <div class="flex flex-col gap-3 mb-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <h3 class="app-section-title">Goals</h3>
                                    @php
                                        $totalExercises = array_sum(array_map(fn($p) => count($p['exercises']), $progressionGanttData['progressions'])) + count($progressionGanttData['standalone']);
                                    @endphp
                                    <span class="text-sm text-white/60">{{ $totalExercises }} exercises</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    @if ($hasGoals)
                                        <button
                                            @click="showOnlyGoals = !showOnlyGoals"
                                            class="text-sm text-emerald-300 hover:text-emerald-200 font-medium flex items-center gap-1"
                                        >
                                            <template x-if="showOnlyGoals">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </template>
                                            <template x-if="!showOnlyGoals">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                </svg>
                                            </template>
                                            <span x-text="showOnlyGoals ? 'Show All' : 'Show Goals Only'"></span>
                                        </button>
                                    @endif
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-white/60">Range:</span>
                                        <div class="inline-flex rounded-full border border-white/10 bg-black/20 p-1">
                                            @foreach ([7, 14, 30] as $range)
                                                <button
                                                    type="button"
                                                    @click="goToDays({{ $range }}, 'progress')"
                                                    class="px-3 py-1.5 text-xs font-semibold rounded-full transition-colors {{ $days === $range ? 'bg-emerald-500/30 text-emerald-200' : 'text-white/60 hover:text-white hover:bg-white/10' }}"
                                                >
                                                    {{ $range }}d
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center justify-center gap-4 text-xs">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-4 h-4 rounded bg-emerald-500"></div>
                                    <span class="text-white/60">Completed</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-4 h-4 rounded bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600"></div>
                                    <span class="text-white/60">Not practiced</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-4 h-4 rounded ring-2 ring-emerald-400"></div>
                                    <span class="text-white/60">Today</span>
                                </div>
                                <div class="text-center text-xs text-white/50">
                                    Numbers in progression groups (1, 2, 3...) show level in the progression path. Box height shows practice duration. Click any box for details.
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="app-card rounded-xl p-3 sm:p-4">
                                <!-- Date Range Header -->
                                <div class="mb-3 text-center">
                                    <span class="text-sm font-medium text-white/60">
                                        {{ $progressionGanttData['date_range']['start'] }} - {{ $progressionGanttData['date_range']['end'] }}
                                    </span>
                                </div>

                                <!-- Column Headers -->
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-28 sm:w-36"></div>
                                    <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                                        @foreach ($progressionGanttData['dayColumns'] as $index => $column)
                                            <div class="text-center {{ $column['is_today'] ? 'bg-emerald-500/10 rounded-t-sm' : '' }}">
                                                <div class="text-[10px] font-semibold {{ $column['is_today'] ? 'text-emerald-300' : 'text-white/50' }}">
                                                    {{ $column['day_name'] }}
                                                </div>
                                                <div class="text-[9px] {{ $column['is_today'] ? 'text-emerald-300/80' : 'text-white/40' }}">
                                                    {{ $column['date'] }}
                                                </div>
                                                @if ($column['is_today'])
                                                    <div class="text-[8px] font-bold text-emerald-300 uppercase">
                                                        Today
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="w-16 sm:w-20"></div>
                                </div>

                                @php
                                    // Calculate global max duration for proportional cell heights
                                    $globalMaxSeconds = max($progressionGanttData['dailyMaxSeconds']);
                                    // Ensure minimum of 60 seconds to prevent division by zero
                                    $globalMaxSeconds = max($globalMaxSeconds, 60);
                                @endphp

                                <div class="space-y-1">
                                    @foreach ($progressionGanttData['progressions'] as $progression)
                                        <!-- Progression Group Header -->
                                        <div class="flex items-center gap-2 py-2 {{ !$loop->first ? 'mt-4 border-t-2 border-white/10' : '' }}">
                                            <div class="w-28 sm:w-36">
                                                <button
                                                    @click="toggleGroup('{{ $progression['path_name'] }}')"
                                                    class="flex items-center gap-1.5 text-white/50 hover:text-white/80 transition-colors"
                                                >
                                                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': !isGroupCollapsed('{{ $progression['path_name'] }}') }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                    <span class="text-sm font-medium text-white/60">{{ count($progression['exercises']) }}</span>
                                                </button>
                                            </div>
                                            <div class="flex-1">
                                                <span class="text-sm font-bold text-emerald-300 uppercase tracking-wide">{{ ucwords($progression['path_name']) }}</span>
                                            </div>
                                        </div>

                                        <div x-show="!isGroupCollapsed('{{ $progression['path_name'] }}')" x-transition>
                                        @foreach ($progression['exercises'] as $exercise)
                                            @php
                                                // Simplified binary color scheme
                                                $cellColorClass = 'bg-emerald-500 dark:bg-emerald-600';
                                                $levelIndicator = 'bg-emerald-500';
                                                // Position indicator for progression level
                                                $position = $exercise['order'];
                                                $positionDisplay = $position + 1;
                                            @endphp
                                            <div class="flex items-center gap-2" x-show="shouldShowExercise('{{ $progression['path_name'] }}', {{ $loop->index }}, {{ count($progression['exercises']) }}) && shouldShowExerciseInGoalMode({{ $exercise['id'] }})">
                                                <!-- Exercise name with progression level -->
                                                <div class="w-28 sm:w-36 flex items-center gap-1.5">
                                                    <span
                                                        class="flex items-center justify-center w-6 h-6 text-[11px] font-bold rounded bg-emerald-500/15 text-emerald-200 flex-shrink-0 border border-emerald-500/40"
                                                        title="Level {{ $positionDisplay }} in {{ ucwords($progression['path_name']) }} progression"
                                                    >
                                                        {{ $positionDisplay }}
                                                    </span>
                                                    <span class="text-xs text-white/80 truncate font-medium" title="{{ $exercise['name'] }}">{{ $exercise['name'] }}</span>
                                                </div>
                                                <!-- Daily cells -->
                                                <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1 items-end">
                                                    @foreach ($exercise['daily_seconds'] as $dayIndex => $seconds)
                                                        @php
                                                            $isToday = $dayIndex === $progressionGanttData['today_index'];
                                                            $todayClass = $isToday ? 'ring-2 ring-emerald-400' : '';

                                                            // Calculate proportional height (min 1rem, max 3rem)
                                                            $heightPercent = $seconds > 0 ? ($seconds / $globalMaxSeconds) * 100 : 0;
                                                            $minHeight = 1; // rem
                                                            $maxHeight = 3; // rem
                                                            $height = $seconds > 0
                                                                ? max($minHeight, min($maxHeight, ($heightPercent / 100) * $maxHeight))
                                                                : $minHeight;
                                                        @endphp
                                                        <div
                                                            class="rounded-sm flex items-center justify-center {{ $seconds > 0 ? $cellColorClass : 'bg-gray-200/30' }} {{ $todayClass }}"
                                                            style="height: {{ $height }}rem;"
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
                                            </div>
                                        @endforeach

                                        <!-- Show More Button -->
                                        <div
                                            class="flex items-center justify-center mt-2"
                                            x-show="getHiddenCount('{{ $progression['path_name'] }}', {{ count($progression['exercises']) }}) > 0"
                                        >
                                            <button
                                                @click="toggleExpanded('{{ $progression['path_name'] }}')"
                                                class="text-xs text-emerald-300 hover:text-emerald-200 font-medium flex items-center gap-1"
                                            >
                                                <template x-if="!isGroupExpanded('{{ $progression['path_name'] }}')">
                                                    <span>
                                                        Show <span x-text="getHiddenCount('{{ $progression['path_name'] }}', {{ count($progression['exercises']) }})"></span> more
                                                        <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                        </svg>
                                                    </span>
                                                </template>
                                                <template x-if="isGroupExpanded('{{ $progression['path_name'] }}')">
                                                    <span>
                                                        Show less
                                                        <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                        </svg>
                                                    </span>
                                                </template>
                                            </button>
                                        </div>
                                        </div>
                                    @endforeach

                                    @if (count($progressionGanttData['standalone']) > 0)
                                        <!-- Standalone exercises section -->
                                        <div class="flex items-center gap-2 py-2 {{ count($progressionGanttData['progressions']) > 0 ? 'mt-4 border-t-2 border-white/10' : '' }}">
                                            <div class="w-28 sm:w-36">
                                                <button
                                                    @click="toggleGroup('standalone')"
                                                    class="flex items-center gap-1.5 text-white/50 hover:text-white/80 transition-colors"
                                                >
                                                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': !isGroupCollapsed('standalone') }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                    <span class="text-sm font-medium text-white/60">{{ count($progressionGanttData['standalone']) }}</span>
                                                </button>
                                            </div>
                                            <div class="flex-1">
                                                <span class="text-sm font-bold text-white/60 uppercase tracking-wide">Other Exercises</span>
                                            </div>
                                        </div>

                                        <div x-show="!isGroupCollapsed('standalone')" x-transition>
                                        @foreach ($progressionGanttData['standalone'] as $exercise)
                                            <div class="flex items-center gap-2" x-show="shouldShowExercise('standalone', {{ $loop->index }}, {{ count($progressionGanttData['standalone']) }}) && shouldShowExerciseInGoalMode({{ $exercise['id'] }})">
                                                    <div class="w-28 sm:w-36 flex items-center gap-1.5">
                                                        <div class="w-6 h-6 flex items-center justify-center flex-shrink-0">
                                                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-400" title="Standalone exercise (not part of a progression)"></div>
                                                    </div>
                                                    <span class="text-xs text-white/80 truncate font-medium" title="{{ $exercise['name'] }}">{{ $exercise['name'] }}</span>
                                                </div>
                                                <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1 items-end">
                                                    @foreach ($exercise['daily_seconds'] as $dayIndex => $seconds)
                                                        @php
                                                            $isToday = $dayIndex === $progressionGanttData['today_index'];
                                                            $todayClass = $isToday ? 'ring-2 ring-emerald-400' : '';

                                                            // Calculate proportional height (min 1rem, max 3rem)
                                                            $heightPercent = $seconds > 0 ? ($seconds / $globalMaxSeconds) * 100 : 0;
                                                            $minHeight = 1; // rem
                                                            $maxHeight = 3; // rem
                                                            $height = $seconds > 0
                                                                ? max($minHeight, min($maxHeight, ($heightPercent / 100) * $maxHeight))
                                                                : $minHeight;
                                                        @endphp
                                                        <div
                                                            class="rounded-sm flex items-center justify-center {{ $seconds > 0 ? 'bg-emerald-500 dark:bg-emerald-600' : 'bg-gray-200 dark:bg-gray-700' }} {{ $todayClass }}"
                                                            style="height: {{ $height }}rem;"
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
                                            </div>
                                        @endforeach

                                        <!-- Show More Button for Standalone -->
                                        <div
                                            class="flex items-center justify-center mt-2"
                                            x-show="getHiddenCount('standalone', {{ count($progressionGanttData['standalone']) }}) > 0"
                                        >
                                            <button
                                                @click="toggleExpanded('standalone')"
                                                class="text-xs text-emerald-300 hover:text-emerald-200 font-medium flex items-center gap-1"
                                            >
                                                <template x-if="!isGroupExpanded('standalone')">
                                                    <span>
                                                        Show <span x-text="getHiddenCount('standalone', {{ count($progressionGanttData['standalone']) }})"></span> more
                                                        <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                        </svg>
                                                    </span>
                                                </template>
                                                <template x-if="isGroupExpanded('standalone')">
                                                    <span>
                                                        Show less
                                                        <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                        </svg>
                                                    </span>
                                                </template>
                                            </button>
                                        </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Day labels -->
                                <div class="flex items-center gap-2 mt-2 pt-2 border-t border-white/10">
                                    <div class="w-28 sm:w-36"></div>
                                    <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                                        @foreach ($progressionGanttData['dayLabels'] as $dayLabel)
                                            <div class="text-[10px] text-white/45 text-center">{{ $dayLabel }}</div>
                                        @endforeach
                                    </div>
                                    <div class="w-16 sm:w-20"></div>
                                </div>

                                <!-- Detail Panel (Modal) -->
                                <div
                                    x-show="showDetailPanel"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    @click.away="closeDetail()"
                                    @keydown.escape.window="closeDetail()"
                                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                    style="display: none;"
                                >
                                    <!-- Backdrop -->
                                    <div class="absolute inset-0 bg-black/50" @click="closeDetail()"></div>

                                    <!-- Panel Content -->
                                    <div class="relative app-panel rounded-2xl max-w-md w-full p-6">
                                        <template x-if="selectedCell">
                                            <div>
                                                <!-- Close Button -->
                                                <button
                                                    @click="closeDetail()"
                                                    class="absolute top-4 right-4 text-white/50 hover:text-white/80"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>

                                                <!-- Exercise Name -->
                                                <h3 class="text-lg font-bold text-white mb-4 pr-8" x-text="selectedCell.exerciseName"></h3>

                                                <!-- Details Grid -->
                                                <div class="space-y-3">
                                                    <!-- Date -->
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-sm text-white/60">Date</span>
                                                        <span class="text-sm font-medium text-white" x-text="selectedCell.date"></span>
                                                    </div>

                                                    <!-- Duration -->
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-sm text-white/60">Duration</span>
                                                        <span class="text-sm font-medium text-white" x-text="formatDuration(selectedCell.seconds)"></span>
                                                    </div>

                                                    <!-- Progression Path (if applicable) -->
                                                    <template x-if="selectedCell.progressionPath">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-sm text-white/60">Progression</span>
                                                            <span class="text-sm font-medium text-white" x-text="selectedCell.progressionPath"></span>
                                                        </div>
                                                    </template>

                                                    <!-- Progression Level (if applicable) -->
                                                    <template x-if="selectedCell.progressionLevel">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-sm text-white/60">Level</span>
                                                            <span class="text-sm font-medium text-white" x-text="'Level ' + selectedCell.progressionLevel"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    @else
                        <!-- Empty State for No Activity -->
                        <div class="app-panel sm:rounded-2xl">
                            <div class="p-8 sm:p-12 text-center">
                                <svg class="mx-auto h-16 w-16 text-white/40 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <h3 class="text-lg font-semibold text-white mb-2">No activity yet</h3>
                                <p class="text-white/60 mb-6 max-w-md mx-auto">
                                    Start practicing to see your progress. Head to the Practices tab to begin your first session.
                                </p>
                                <button
                                    @click="activeTab = 'templates'; updateUrl('templates')"
                                    class="inline-flex items-center px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-lg transition-colors"
                                >
                                    View Practices
                                </button>
                            </div>
                        </div>
                    @endif
                    @elseif ($selectedDiscipline === 'soccer')
                        <div class="grid gap-6 lg:grid-cols-3">
                            <div class="app-panel sm:rounded-2xl lg:col-span-2">
                                <div class="p-6">
                                    <div class="flex items-center justify-between gap-4 mb-4">
                                        <div>
                                            <h3 class="app-section-title">Program Progress</h3>
                                            <p class="text-sm text-white/60">Today, weekly adherence, and benchmark-linked training context.</p>
                                        </div>
                                        @if (($soccerDashboard['active_program'] ?? null) !== null)
                                            <span class="rounded-full border border-emerald-400/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-200">
                                                {{ $soccerDashboard['active_program']['name'] }}
                                            </span>
                                        @endif
                                    </div>

                                    @if (($soccerDashboard['active_program'] ?? null) !== null)
                                        @php
                                            $todayDay = $soccerDashboard['today_day'] ?? null;
                                            $weeklySummary = $soccerDashboard['weekly_summary'] ?? null;
                                        @endphp
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="app-card app-card--nested rounded-xl p-4">
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/45">Today's Prescription</div>
                                                @if ($todayDay)
                                                    <div class="mt-3 space-y-2">
                                                        <div class="text-xl font-semibold text-white">{{ $todayDay['title'] ?? 'Practice Day' }}</div>
                                                        @if (!empty($todayDay['description']))
                                                            <p class="text-sm text-white/60">{{ $todayDay['description'] }}</p>
                                                        @endif
                                                        <div class="flex flex-wrap gap-2 text-xs text-white/55">
                                                            <span>Week {{ $todayDay['week'] }}</span>
                                                            <span>Day {{ $todayDay['day'] }}</span>
                                                            <span>Status: {{ str_replace('_', ' ', $todayDay['status'] ?? 'pending') }}</span>
                                                        </div>
                                                    </div>
                                                @else
                                                    <p class="mt-3 text-sm text-white/60">No active prescription is scheduled for today.</p>
                                                @endif
                                            </div>

                                            <div class="app-card app-card--nested rounded-xl p-4">
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/45">{{ $soccerDashboard['soccer_load']['window_label'] ?? 'Load' }}</div>
                                                <div class="mt-3 flex items-end gap-3">
                                                    <div class="text-3xl font-semibold text-white">
                                                        <x-duration-display :seconds="$soccerDashboard['soccer_load']['total_seconds'] ?? 0" />
                                                    </div>
                                                    <div class="pb-1 text-sm text-white/55">{{ $soccerDashboard['soccer_load']['session_count'] ?? 0 }} sessions</div>
                                                </div>
                                                @if ($weeklySummary)
                                                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                                        <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2">
                                                            <div class="text-white/45">Completed</div>
                                                            <div class="mt-1 font-semibold text-white">{{ $weeklySummary['completed_count'] }}/{{ $weeklySummary['planned_count'] }}</div>
                                                        </div>
                                                        <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2">
                                                            <div class="text-white/45">Rest Days</div>
                                                            <div class="mt-1 font-semibold text-white">{{ $weeklySummary['rest_count'] }}</div>
                                                        </div>
                                                        <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2">
                                                            <div class="text-white/45">Skipped</div>
                                                            <div class="mt-1 font-semibold text-white">{{ $weeklySummary['skipped_count'] }}</div>
                                                        </div>
                                                        <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2">
                                                            <div class="text-white/45">Moved</div>
                                                            <div class="mt-1 font-semibold text-white">{{ $weeklySummary['moved_count'] }}</div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="rounded-xl border border-dashed border-white/15 bg-white/5 p-6 text-center">
                                            <h4 class="text-lg font-semibold text-white">No active soccer program</h4>
                                            <p class="mt-2 text-sm text-white/60">Start one from the Practices tab to unlock daily prescriptions and adherence tracking.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="app-panel sm:rounded-2xl">
                                <div class="p-6">
                                    <h3 class="app-section-title">Upcoming</h3>
                                    <div class="mt-4 space-y-3">
                                        @forelse (($soccerDashboard['upcoming_days'] ?? []) as $day)
                                            <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div>
                                                        <div class="text-sm font-semibold text-white">{{ $day['title'] ?? 'Practice Day' }}</div>
                                                        <div class="text-xs text-white/55">{{ \Carbon\Carbon::parse($day['scheduled_for'])->format('M j') }} • Week {{ $day['week'] }} Day {{ $day['day'] }}</div>
                                                    </div>
                                                    <span class="text-xs uppercase tracking-[0.18em] text-white/40">{{ str_replace('_', ' ', $day['status'] ?? 'pending') }}</span>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-sm text-white/60">Nothing upcoming yet.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-6 xl:grid-cols-2">
                            @foreach (($soccerDashboard['assessment_cards'] ?? []) as $assessmentCard)
                                @php
                                    $assessment = $assessmentCard['assessment'];
                                    $latest = $assessmentCard['latest'];
                                    $trend = $assessmentCard['trend'];
                                @endphp
                                <div class="app-panel sm:rounded-2xl">
                                    <div class="p-6">
                                        <div class="flex flex-wrap items-start justify-between gap-4">
                                            <div>
                                                <h3 class="text-xl font-semibold text-white">{{ $assessment['name'] }}</h3>
                                                <p class="mt-1 text-sm text-white/60">{{ $assessment['description'] }}</p>
                                            </div>
                                            @if ($latest)
                                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $latest->derived_status === 'pass' ? 'bg-emerald-500/15 text-emerald-200 border border-emerald-400/30' : 'bg-cyan-500/15 text-cyan-200 border border-cyan-400/30' }}">
                                                    {{ str_replace('_', ' ', $latest->derived_status) }}
                                                </span>
                                            @endif
                                        </div>

                                        @if ($latest)
                                            <div class="mt-4 rounded-xl border border-white/10 bg-white/5 p-4">
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/45">Latest Result</div>
                                                <div class="mt-2 text-sm font-medium text-white">{{ $latest->summary_label }}</div>
                                                <div class="mt-1 text-xs text-white/50">{{ $latest->recorded_on?->format('M j, Y') }}</div>
                                                @if ($trend)
                                                    <div class="mt-3 text-xs {{ $trend['direction'] === 'improved' ? 'text-emerald-300' : 'text-amber-300' }}">
                                                        Trend: {{ $trend['label'] }} vs previous
                                                    </div>
                                                @endif
                                            </div>

                                            @if (!empty($assessmentCard['comparison_rows']))
                                                <div class="mt-4 space-y-2">
                                                    @foreach ($assessmentCard['comparison_rows'] as $row)
                                                        <div class="flex items-center justify-between rounded-lg border border-white/10 bg-black/10 px-3 py-2 text-sm">
                                                            <span class="text-white/70">{{ $row['label'] }}</span>
                                                            <span class="text-white">
                                                                {{ $row['actual'] ?? 'n/a' }}
                                                                @if ($row['target'])
                                                                    <span class="text-white/40">/ {{ $row['target'] }}</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                    </div>
                                                @endif
                                        @else
                                            <div class="mt-4 rounded-xl border border-dashed border-white/15 bg-white/5 p-4">
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/45">No Baseline Logged Yet</div>
                                                <p class="mt-2 text-sm text-white/65">Use this form for your first benchmark so you can compare later weeks against a real starting point.</p>
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('assessment-results.store') }}" class="mt-6 space-y-3">
                                            @csrf
                                            <input type="hidden" name="assessment_slug" value="{{ $assessment['slug'] }}">
                                            <div>
                                                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-white/45">Recorded On</label>
                                                <input type="date" name="recorded_on" value="{{ old('recorded_on', $userNow->toDateString()) }}" class="w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white">
                                            </div>
                                            @foreach ($assessment['input_schema'] as $field)
                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-white/45">{{ $field['label'] }}</label>
                                                    @if (($field['type'] ?? null) === 'multiline')
                                                        <textarea
                                                            name="results[{{ $field['key'] }}]"
                                                            rows="3"
                                                            placeholder="{{ $field['placeholder'] ?? '' }}"
                                                            class="w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white placeholder:text-white/30"
                                                        >{{ old("results.{$field['key']}") }}</textarea>
                                                    @else
                                                        <input
                                                            type="text"
                                                            name="results[{{ $field['key'] }}]"
                                                            value="{{ old("results.{$field['key']}") }}"
                                                            placeholder="{{ isset($field['target_seconds']) ? 'Target '.gmdate('i:s', $field['target_seconds']) : 'm:ss or seconds' }}"
                                                            class="w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white placeholder:text-white/30"
                                                        >
                                                    @endif
                                                </div>
                                            @endforeach
                                            <div>
                                                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-white/45">Notes</label>
                                                <textarea name="notes" rows="2" class="w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white placeholder:text-white/30"></textarea>
                                            </div>
                                            <button type="submit" class="w-full rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-600">
                                                Log Benchmark
                                            </button>
                                        </form>

                                        @if (!empty($assessmentCard['history']))
                                            <div class="mt-5 border-t border-white/10 pt-4">
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/45">Recent History</div>
                                                <div class="mt-3 space-y-2">
                                                    @foreach ($assessmentCard['history'] as $history)
                                                        <div class="flex items-center justify-between text-sm text-white/70">
                                                            <span>{{ $history['recorded_on']?->format('M j') }}</span>
                                                            <span>{{ $history['summary_label'] }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($selectedDiscipline === 'meditation')
                        <x-dashboard.meditation-dashboard :isDisciplineLive="$isDisciplineLive" />
                    @elseif ($selectedDiscipline === 'lifting')
                        <x-dashboard.lifting-dashboard :isDisciplineLive="$isDisciplineLive" />
                    @else
                        <div class="app-panel sm:rounded-2xl">
                            <div class="p-8 sm:p-12 text-center">
                                <h3 class="text-lg font-semibold text-white mb-2">{{ $disciplines[$selectedDiscipline]['label'] ?? ucfirst($selectedDiscipline) }} is coming soon</h3>
                                <p class="text-white/60 max-w-md mx-auto">The discipline switcher is live, but the training surface for this discipline has not been built yet.</p>
                            </div>
                        </div>
                    @endif
                </div> <!-- END Goals Tab Content -->

                <!-- Practices Tab Content -->
                <div x-show="activeTab === 'templates'" x-transition class="mt-6" role="tabpanel" id="templates-panel" aria-labelledby="templates-tab">
                    @if ($selectedDiscipline === 'soccer')
                        @php
                            $activeEnrollment = $soccerDashboard['active_enrollment'] ?? null;
                            $activeProgram = $soccerDashboard['active_program'] ?? null;
                            $todayDay = $soccerDashboard['today_day'] ?? null;
                            $quickStart = $soccerDashboard['quick_start'] ?? ['team_tracks' => [], 'conditioning_programs' => [], 'baseline_assessments' => []];
                        @endphp

                        @if ($activeProgram)
                            <div class="app-panel sm:rounded-2xl mb-6">
                                <div class="p-6">
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div>
                                            <div class="text-xs uppercase tracking-[0.24em] text-white/45">Active Program</div>
                                            <h3 class="mt-2 text-2xl font-semibold text-white">{{ $activeProgram['name'] }}</h3>
                                            <p class="mt-1 text-sm text-white/60">{{ $activeProgram['description'] }}</p>
                                        </div>
                                        <div class="rounded-full border border-emerald-400/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-200">
                                            Started {{ $activeEnrollment->starts_on?->format('M j, Y') }}
                                        </div>
                                    </div>

                                    <div class="mt-6 grid gap-4 lg:grid-cols-[1.3fr_0.7fr]">
                                            <div class="app-card app-card--nested rounded-xl p-4">
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/45">Today</div>
                                                @if ($todayDay)
                                                    @php
                                                        $nextTrainingDay = collect($soccerDashboard['upcoming_days'] ?? [])
                                                            ->first(fn (array $candidate) => ! ($candidate['is_rest_day'] ?? false));
                                                    @endphp
                                                    <div class="mt-3 space-y-3">
                                                        <div>
                                                            <div class="text-xl font-semibold text-white">{{ $todayDay['title'] ?? 'Practice Day' }}</div>
                                                            <div class="text-sm text-white/55">{{ \Carbon\Carbon::parse($todayDay['scheduled_for'])->format('l, M j') }} • Week {{ $todayDay['week'] }} Day {{ $todayDay['day'] }}</div>
                                                        </div>
                                                        @if (!empty($todayDay['description']))
                                                            <p class="text-sm text-white/60">{{ $todayDay['description'] }}</p>
                                                        @endif
                                                        @if (!empty($todayDay['assessment_name']))
                                                            <div class="rounded-lg border border-cyan-400/20 bg-cyan-500/10 px-4 py-3 text-sm text-cyan-100">
                                                                This day pairs with the {{ $todayDay['assessment_name'] }}. Finish the field work, then log the result from Progress &amp; Benchmarks.
                                                            </div>
                                                        @endif
                                                        @if (($todayDay['is_rest_day'] ?? false) && $nextTrainingDay)
                                                            <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3 text-sm text-white/70">
                                                                Next field session: <span class="font-semibold text-white">{{ $nextTrainingDay['title'] ?? 'Practice Day' }}</span> on {{ \Carbon\Carbon::parse($nextTrainingDay['scheduled_for'])->format('M j') }}.
                                                            </div>
                                                        @endif
                                                        <div class="flex flex-wrap gap-3">
                                                            @if (!($todayDay['is_rest_day'] ?? false) && !empty($todayDay['template_slug']))
                                                                <a href="{{ route('go.index', ['template_slug' => $todayDay['template_slug'], 'discipline' => 'soccer', 'program_enrollment' => $activeEnrollment->id, 'program_day_key' => $todayDay['key'], 'scheduled_for' => $todayDay['scheduled_for']]) }}" class="inline-flex items-center rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-emerald-600">
                                                                    Start Today's Practice
                                                            </a>
                                                        @endif
                                                        @if ($todayDay['is_rest_day'] ?? false)
                                                            <span class="inline-flex items-center rounded-lg border border-cyan-400/30 bg-cyan-500/10 px-4 py-2 text-sm font-semibold text-cyan-200">
                                                                Recovery Day
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if (($todayDay['status'] ?? 'pending') === 'pending' && !($todayDay['is_rest_day'] ?? false))
                                                        @php
                                                            $moveTo = \Carbon\Carbon::parse($todayDay['scheduled_for'])->addDay()->toDateString();
                                                        @endphp
                                                        <div class="flex flex-wrap gap-3 border-t border-white/10 pt-4">
                                                            <form method="POST" action="{{ route('training-programs.skip', $activeEnrollment) }}">
                                                                @csrf
                                                                <input type="hidden" name="scheduled_for" value="{{ $todayDay['scheduled_for'] }}">
                                                                <button type="submit" class="rounded-lg border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 transition-colors hover:bg-white/10">
                                                                    Skip Day
                                                                </button>
                                                            </form>
                                                            <form method="POST" action="{{ route('training-programs.move', $activeEnrollment) }}">
                                                                @csrf
                                                                <input type="hidden" name="scheduled_for" value="{{ $todayDay['scheduled_for'] }}">
                                                                <input type="hidden" name="move_to" value="{{ $moveTo }}">
                                                                <button type="submit" class="rounded-lg border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white/80 transition-colors hover:bg-white/10">
                                                                    Move To Tomorrow
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <p class="mt-3 text-sm text-white/60">No day is currently scheduled. Start a new program below.</p>
                                            @endif
                                        </div>

                                        <div class="app-card app-card--nested rounded-xl p-4">
                                            <div class="text-xs uppercase tracking-[0.24em] text-white/45">Upcoming</div>
                                            <div class="mt-3 space-y-3">
                                                @forelse (($soccerDashboard['upcoming_days'] ?? []) as $day)
                                                    <div class="rounded-lg border border-white/10 bg-black/10 px-3 py-3">
                                                        <div class="text-sm font-semibold text-white">{{ $day['title'] ?? 'Practice Day' }}</div>
                                                        <div class="mt-1 text-xs text-white/50">{{ \Carbon\Carbon::parse($day['scheduled_for'])->format('M j') }} • {{ str_replace('_', ' ', $day['status'] ?? 'pending') }}</div>
                                                    </div>
                                                @empty
                                                    <p class="text-sm text-white/60">No upcoming days scheduled yet.</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (! $activeProgram)
                            <div class="app-panel sm:rounded-2xl mb-6">
                                <div class="p-6">
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div>
                                            <div class="text-xs uppercase tracking-[0.24em] text-white/45">Start Here</div>
                                            <h3 class="mt-2 text-2xl font-semibold text-white">Pick the track that matches how you already train</h3>
                                            <p class="mt-2 max-w-3xl text-sm text-white/60">Choose a start date and the app will turn the document into a day-by-day soccer plan with today’s prescription, upcoming sessions, and benchmark tracking.</p>
                                        </div>
                                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-white/65">New soccer setup</span>
                                    </div>

                                    <div class="mt-6 grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
                                        <div class="space-y-4">
                                            <div class="app-card app-card--nested rounded-xl p-4">
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/45">What To Expect</div>
                                                <div class="mt-3 space-y-3 text-sm text-white/70">
                                                    <div><span class="font-semibold text-white">1.</span> Choose the weekly team track if you already practice with a team, or the 6-week block if you want a dedicated conditioning cycle.</div>
                                                    <div><span class="font-semibold text-white">2.</span> Your chosen start date becomes day one, and the app will surface exactly what to do today and next.</div>
                                                    <div><span class="font-semibold text-white">3.</span> Use Progress &amp; Benchmarks to log baseline fitness tests and compare later improvements.</div>
                                                </div>
                                            </div>

                                            <div class="app-card app-card--nested rounded-xl p-4">
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/45">Baseline Benchmarks</div>
                                                <div class="mt-3 space-y-2">
                                                    @foreach ($quickStart['baseline_assessments'] as $assessment)
                                                        <div class="flex items-start justify-between gap-3 rounded-lg border border-white/10 bg-black/10 px-3 py-3">
                                                            <div>
                                                                <div class="text-sm font-semibold text-white">{{ $assessment['name'] }}</div>
                                                                <div class="mt-1 text-xs text-white/55">{{ $assessment['description'] }}</div>
                                                            </div>
                                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $assessment['has_result'] ? 'border border-emerald-400/30 bg-emerald-500/10 text-emerald-200' : 'border border-white/10 bg-white/5 text-white/60' }}">
                                                                {{ $assessment['has_result'] ? 'Logged' : 'Recommended' }}
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid gap-4 lg:grid-cols-2">
                                            <div
                                                class="app-card app-card--stack rounded-2xl p-4"
                                                x-data="{
                                                    teamTracks: {{ Js::from($quickStart['team_tracks']) }},
                                                    selectedSlug: '',
                                                    get selectedProgram() {
                                                        return this.teamTracks.find((program) => program.slug === this.selectedSlug) ?? null;
                                                    }
                                                }"
                                            >
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/45">Team Schedule Track</div>
                                                <h4 class="mt-2 text-lg font-semibold text-white">Start around my team practices</h4>
                                                <p class="mt-1 text-sm text-white/60">Pick the Stanford track that matches your weekly team load.</p>

                                                <form method="POST" action="{{ route('training-programs.store') }}" class="mt-4 space-y-3">
                                                    @csrf
                                                    <div>
                                                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-white/45">Team Sessions Per Week</label>
                                                        <select x-model="selectedSlug" name="program_slug" required class="w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white">
                                                            <option value="" class="bg-slate-900 text-white">Choose weekly team sessions</option>
                                                            @foreach ($quickStart['team_tracks'] as $program)
                                                                <option value="{{ $program['slug'] }}" class="bg-slate-900 text-white">{{ $program['name'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-white/45">Start Date</label>
                                                        <input type="date" name="starts_on" value="{{ $userNow->toDateString() }}" class="w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white">
                                                    </div>
                                                    <button type="submit" :disabled="!selectedSlug" class="w-full rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-50">
                                                        Start Stanford Track
                                                    </button>
                                                </form>

                                                <div class="mt-4 rounded-xl border border-white/10 bg-black/10 p-4">
                                                    <div class="text-xs uppercase tracking-[0.24em] text-white/45">Preview</div>
                                                    <template x-if="selectedProgram">
                                                        <div class="mt-3 space-y-3">
                                                            <p class="text-sm text-white/70" x-text="selectedProgram.recommended_for"></p>
                                                            <div class="flex flex-wrap gap-2 text-xs text-white/50">
                                                                <span x-text="selectedProgram.training_days_per_cycle + ' training days'"></span>
                                                                <span x-text="selectedProgram.rest_days_per_cycle + ' rest days'"></span>
                                                                <span x-show="selectedProgram.assessment_count > 0" x-text="selectedProgram.assessment_count + ' benchmark' + (selectedProgram.assessment_count === 1 ? '' : 's')"></span>
                                                            </div>
                                                            <div class="space-y-2">
                                                                <template x-for="day in selectedProgram.preview_days.slice(0, 3)" :key="day.key">
                                                                    <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-3">
                                                                        <div class="text-[11px] uppercase tracking-[0.2em] text-white/40" x-text="'Day ' + day.day"></div>
                                                                        <div class="mt-1 text-sm font-semibold text-white" x-text="day.title"></div>
                                                                        <div class="mt-1 text-xs text-white/55" x-text="day.is_rest_day ? 'Recovery / rest day' : (day.assessment_name ? 'Includes ' + day.assessment_name : 'Practice session')"></div>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <template x-if="!selectedProgram">
                                                        <p class="mt-3 text-sm text-white/50">Choose your weekly team schedule to preview the first few days.</p>
                                                    </template>
                                                </div>
                                            </div>

                                            @foreach ($quickStart['conditioning_programs'] as $program)
                                                <div class="app-card app-card--stack rounded-2xl p-4">
                                                    <div class="text-xs uppercase tracking-[0.24em] text-white/45">6-Week Conditioning Block</div>
                                                    <h4 class="mt-2 text-lg font-semibold text-white">{{ $program['name'] }}</h4>
                                                    <p class="mt-1 text-sm text-white/60">{{ $program['description'] }}</p>

                                                    <div class="mt-4 flex flex-wrap gap-2 text-xs text-white/50">
                                                        <span>{{ $program['training_days_per_cycle'] }} training days</span>
                                                        <span>{{ $program['rest_days_per_cycle'] }} rest days</span>
                                                        <span>{{ $program['duration_weeks'] }} weeks</span>
                                                    </div>

                                                    <p class="mt-3 text-sm text-white/70">{{ $program['recommended_for'] }}</p>

                                                    <div class="mt-4 rounded-xl border border-white/10 bg-black/10 p-4">
                                                        <div class="text-xs uppercase tracking-[0.24em] text-white/45">Preview</div>
                                                        <div class="mt-3 space-y-2">
                                                            @foreach (collect($program['preview_days'] ?? [])->take(3) as $day)
                                                                <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-3">
                                                                    <div class="text-[11px] uppercase tracking-[0.2em] text-white/40">Day {{ $day['day'] }}</div>
                                                                    <div class="mt-1 text-sm font-semibold text-white">{{ $day['title'] ?? 'Practice Day' }}</div>
                                                                    <div class="mt-1 text-xs text-white/55">{{ ($day['is_rest_day'] ?? false) ? 'Recovery / rest day' : 'Practice session' }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>

                                                    <form method="POST" action="{{ route('training-programs.store') }}" class="mt-4 space-y-3">
                                                        @csrf
                                                        <input type="hidden" name="program_slug" value="{{ $program['slug'] }}">
                                                        <div>
                                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-white/45">Start Date</label>
                                                            <input type="date" name="starts_on" value="{{ $userNow->toDateString() }}" class="w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white">
                                                        </div>
                                                        <button type="submit" class="w-full rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-600">
                                                            Start 6-Week Block
                                                        </button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="app-panel sm:rounded-2xl mb-6">
                                <div class="p-6">
                                    <h3 class="text-xl font-bold text-white">Program Library</h3>
                                <p class="mt-1 text-sm text-white/60">Training tracks from the soccer documents. Choose one and set your start date.</p>
                                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                    @foreach (($soccerDashboard['programs'] ?? []) as $program)
                                        <div class="app-card app-card--stack rounded-2xl p-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <h4 class="text-lg font-semibold text-white">{{ $program['name'] }}</h4>
                                                    <p class="mt-1 text-sm text-white/60">{{ $program['description'] }}</p>
                                                </div>
                                                @if (($activeProgram['slug'] ?? null) === $program['slug'])
                                                    <span class="rounded-full border border-emerald-400/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-200">Active</span>
                                                @endif
                                            </div>
                                            <div class="mt-4 flex flex-wrap gap-3 text-xs text-white/50">
                                                <span>{{ $program['duration_weeks'] }} week{{ $program['duration_weeks'] === 1 ? '' : 's' }}</span>
                                                <span>{{ $program['training_days_per_cycle'] ?? 0 }} training days</span>
                                                <span>{{ $program['rest_days_per_cycle'] ?? 0 }} rest days</span>
                                                @if (!empty($program['team_practice_band']))
                                                    <span>{{ str_replace('_', ' ', $program['team_practice_band']) }}</span>
                                                @endif
                                                @if (($program['assessment_count'] ?? 0) > 0)
                                                    <span>{{ $program['assessment_count'] }} benchmark{{ $program['assessment_count'] === 1 ? '' : 's' }}</span>
                                                @endif
                                            </div>
                                            @if (!empty($program['recommended_for']))
                                                <p class="mt-3 text-sm text-white/65">{{ $program['recommended_for'] }}</p>
                                            @endif
                                            <div class="mt-4 rounded-xl border border-white/10 bg-black/10 p-4">
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/45">Week 1 Preview</div>
                                                <div class="mt-3 space-y-2">
                                                    @foreach (collect($program['preview_days'] ?? [])->take(3) as $day)
                                                        <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-3">
                                                            <div class="text-[11px] uppercase tracking-[0.2em] text-white/40">Day {{ $day['day'] }}</div>
                                                            <div class="mt-1 text-sm font-semibold text-white">{{ $day['title'] ?? 'Practice Day' }}</div>
                                                            <div class="mt-1 text-xs text-white/55">
                                                                @if ($day['is_rest_day'] ?? false)
                                                                    Recovery / rest day
                                                                @elseif (!empty($day['assessment_name']))
                                                                    Includes {{ $day['assessment_name'] }}
                                                                @else
                                                                    Practice session
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('training-programs.store') }}" class="mt-4 flex flex-wrap items-end gap-3">
                                                @csrf
                                                <input type="hidden" name="program_slug" value="{{ $program['slug'] }}">
                                                @if (!empty($program['team_practice_band']))
                                                    <input type="hidden" name="team_practice_band" value="{{ $program['team_practice_band'] }}">
                                                @endif
                                                <div class="min-w-[11rem] flex-1">
                                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-white/45">Start Date</label>
                                                    <input type="date" name="starts_on" value="{{ $userNow->toDateString() }}" class="w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white">
                                                </div>
                                                <button type="submit" class="rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-600">
                                                    Start Program
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="app-panel sm:rounded-2xl mb-6">
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-white">Practice Library</h3>
                                <p class="mt-1 text-sm text-white/60">Standalone sessions from the soccer documents. Use these directly or use them to round out your week.</p>
                                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                    @foreach (($soccerDashboard['practice_library'] ?? []) as $practice)
                                        <div class="app-card app-card--stack rounded-2xl p-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <h4 class="text-lg font-semibold text-white">{{ $practice['name'] }}</h4>
                                                    @if (!empty($practice['description']))
                                                        <p class="mt-1 text-sm text-white/60">{{ $practice['description'] }}</p>
                                                    @endif
                                                </div>
                                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/55">{{ count($practice['blocks'] ?? []) }} blocks</span>
                                            </div>
                                            <div class="mt-4 flex justify-end">
                                                <a href="{{ route('go.index', ['template_slug' => $practice['slug'], 'discipline' => 'soccer']) }}" class="inline-flex items-center rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-emerald-600">
                                                    Practice
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        @if (($soccerDashboard['user_templates'] ?? collect())->isNotEmpty())
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-white">My Soccer Templates</h3>
                                        <p class="text-sm text-white/60">User-owned copies or variations of soccer practices.</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    @foreach ($soccerDashboard['user_templates'] as $template)
                                        <x-template-card :template="$template" :allExercises="$allExercises" />
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @elseif ($selectedDiscipline === 'general')
                        @if ($userCarouselData->isEmpty())
                            <div class="app-panel sm:rounded-2xl" x-data="{ created: false, cardHtml: '' }">
                                <div class="p-6 text-white">
                                    <template x-if="!created">
                                        <div class="max-w-2xl">
                                            <div class="mb-6">
                                                <h2 class="text-xl font-bold text-white mb-2">Get Started with Daily Calisthenics</h2>
                                                <p class="text-white/60 mb-4">Choose a starter template below to begin your practice, or create your own custom template.</p>
                                            </div>

                                            <div class="grid gap-3 sm:grid-cols-2 mb-6">
                                                @forelse($systemTemplates as $starter)
                                                    <a href="{{ route('home') }}?template={{ $starter->id }}&tab=templates" class="block p-4 app-card rounded-xl transition-all group">
                                                        <div class="flex items-start justify-between mb-2">
                                                            <h3 class="font-semibold text-white group-hover:text-emerald-200">{{ $starter->name }}</h3>
                                                            <span class="text-xs text-white/50">{{ $starter->exercises->count() }} exercises</span>
                                                        </div>
                                                        <p class="text-sm text-white/60">Click to view and copy</p>
                                                    </a>
                                                @empty
                                                    <div class="sm:col-span-2 text-sm text-white/50">
                                                        No starter templates yet. Create a blank template to get started.
                                                    </div>
                                                @endforelse
                                            </div>

                                            <div class="pt-4 border-t border-white/10">
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
                                                            window.location.href = '{{ route('home') }}?tab=templates&template=' + template.id;
                                                        })
                                                        .catch(() => {
                                                            $el.disabled = false;
                                                            $el.querySelector('span').textContent = 'Create Blank Template';
                                                            alert('Failed to create template');
                                                        });
                                                    "
                                                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg transition-colors disabled:opacity-50"
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
                            @if ($systemTemplates->isNotEmpty())
                                <div class="app-panel sm:rounded-2xl mb-6">
                                    <div class="p-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <div>
                                                <h3 class="text-xl font-bold text-white">Starter Templates</h3>
                                                <p class="text-sm text-white/60">Quick starts you can copy or practice as-is.</p>
                                            </div>
                                        </div>
                                        <div class="grid gap-4 md:grid-cols-2">
                                            @foreach ($systemTemplates as $starter)
                                                <x-template-card :template="$starter" :allExercises="$allExercises" />
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="space-y-6">
                                @foreach ($userCarouselData as $carouselData)
                                    @php
                                        $allExercisesInWeek = collect($carouselData['weeklyBreakdown'])
                                            ->flatMap(fn($day) => collect($day['exercises'])->pluck('name'))
                                            ->unique()
                                            ->values()
                                            ->toArray();
                                    @endphp
                                    <div>
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-center gap-3">
                                                <h3 class="text-xl font-bold text-white">
                                                    {{ $carouselData['user']->name }}
                                                </h3>
                                                <div class="flex items-center gap-2 px-2 py-1 bg-cyan-500/10 border border-cyan-400/30 rounded">
                                                    <svg class="w-4 h-4 text-cyan-300" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <span class="font-bold text-cyan-200 text-sm">{{ $carouselData['currentStreak'] }}</span>
                                                </div>
                                            </div>

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
                                                            window.location.href = '{{ route('home') }}?tab=templates&template=' + template.id;
                                                        })
                                                        .catch((e) => {
                                                            console.error(e);
                                                            $el.disabled = false;
                                                            $el.querySelector('span').textContent = 'New Practice';
                                                            alert('Failed to create template');
                                                        });
                                                    "
                                                    class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-emerald-200 bg-emerald-500/15 border border-emerald-400/30 rounded-lg hover:bg-emerald-500/25 transition-colors disabled:opacity-50"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                    </svg>
                                                    <span>New Practice</span>
                                                </button>
                                            @endif
                                        </div>

                                        @if ($carouselData['user']->id !== auth()->id() && count($allExercisesInWeek) > 0)
                                            <div class="mb-6 app-panel sm:rounded-2xl p-4 sm:p-6"
                                                x-data="{
                                                    weeklyData: {{ Js::from($carouselData['weeklyBreakdown']) }},
                                                    allExercises: {{ Js::from($allExercisesInWeek) }}
                                                }">
                                                <div class="app-card rounded-xl p-3 sm:p-4">
                                                    <div class="text-sm font-semibold text-white/80 mb-3">This Week</div>
                                                    <div class="space-y-2">
                                                        <template x-for="(exercise, exIndex) in allExercises" :key="exIndex">
                                                            <div class="flex items-center gap-2">
                                                                <div class="w-20 sm:w-24 text-xs text-white/60 truncate" x-text="exercise"></div>
                                                                <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                                                                    <template x-for="(day, dayIdx) in weeklyData" :key="dayIdx">
                                                                        <div
                                                                            class="h-3 sm:h-4 rounded-sm transition-colors"
                                                                            :class="{
                                                                                'bg-emerald-500': day.exercises.some(e => e.name === exercise),
                                                                                'bg-gray-200/30': !day.exercises.some(e => e.name === exercise)
                                                                            }"
                                                                        ></div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <div class="flex items-center gap-2 mt-2 pt-2 border-t border-white/10">
                                                        <div class="w-20 sm:w-24"></div>
                                                        <div class="flex-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                                                            @foreach ($carouselData['weeklyBreakdown'] as $day)
                                                                <div class="text-[10px] text-white/45 text-center">{{ substr($day['dayName'], 0, 1) }}</div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                            @foreach ($carouselData['templates'] as $template)
                                                <x-template-card :template="$template" :allExercises="$allExercises" />
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @elseif ($selectedDiscipline === 'meditation')
                        <x-dashboard.meditation-dashboard :isDisciplineLive="$isDisciplineLive" />
                    @elseif ($selectedDiscipline === 'lifting')
                        <x-dashboard.lifting-dashboard :isDisciplineLive="$isDisciplineLive" />
                    @else
                        <div class="app-panel sm:rounded-2xl">
                            <div class="p-8 sm:p-12 text-center">
                                <h3 class="text-lg font-semibold text-white mb-2">{{ $disciplines[$selectedDiscipline]['label'] ?? ucfirst($selectedDiscipline) }} practices are not live yet</h3>
                                <p class="text-white/60 max-w-md mx-auto">The switcher is ready, but this discipline does not yet have a practice library or program flow.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
