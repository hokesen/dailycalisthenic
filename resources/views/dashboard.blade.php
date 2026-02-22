@use('Illuminate\Support\Js')
<x-app-layout>
    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-dashboard.welcome-bar
                :user="auth()->user()"
                :hasPracticed="$hasPracticedToday"
                :streak="$authUserStreak"
            />

            <!-- Tab Navigation -->
            <div class="mb-6 app-reveal" x-data="{
                activeTab: {{ Js::from(request()->input('tab', 'timeline')) }},
                updateUrl(tab) {
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tab);
                    history.replaceState(null, '', url);
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
                            Goals
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

                    <!-- Filter Controls -->
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="app-section-title">Practice Log</h3>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-white/60">Show:</span>
                            <select
                                onchange="window.location.href = '{{ route('home') }}?days=' + this.value"
                                class="text-sm rounded-md px-3 py-1 app-field focus:ring-0 focus:outline-none"
                            >
                                <option value="7" {{ $days == 7 ? 'selected' : '' }}>7 days</option>
                                <option value="14" {{ $days == 14 ? 'selected' : '' }}>14 days</option>
                                <option value="30" {{ $days == 30 ? 'selected' : '' }}>30 days</option>
                                <option value="90" {{ $days == 90 ? 'selected' : '' }}>90 days</option>
                            </select>
                        </div>
                    </div>

                    <!-- Timeline Feed -->

                    <x-timeline.feed :timelineFeed="$timelineFeed" :timezone="$userTimezone" :userNow="$userNow" />
                </div>

                <!-- Goals Tab Content -->
                <div x-show="activeTab === 'progress'" x-transition class="mt-6" role="tabpanel" id="progress-panel" aria-labelledby="progress-tab">
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
                                        <span class="text-sm text-white/60">Show:</span>
                                        <select
                                            onchange="window.location.href = '{{ route('home') }}?days=' + this.value + '&tab=progress'"
                                            class="text-sm rounded-md px-3 py-1 app-field focus:ring-0 focus:outline-none"
                                        >
                                            <option value="7" {{ $days == 7 ? 'selected' : '' }}>7 days</option>
                                            <option value="14" {{ $days == 14 ? 'selected' : '' }}>14 days</option>
                                            <option value="30" {{ $days == 30 ? 'selected' : '' }}>30 days</option>
                                        </select>
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
                </div> <!-- END Goals Tab Content -->

                <!-- Practices Tab Content -->
                <div x-show="activeTab === 'templates'" x-transition class="mt-6" role="tabpanel" id="templates-panel" aria-labelledby="templates-tab">
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

                            <!-- Gantt Chart for Exercises (Only for other users, auth user has it in the combined section above) -->
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
                                        <!-- Day labels -->
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

                            <!-- Practices Grid -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                @foreach ($carouselData['templates'] as $template)
                                    <x-template-card :template="$template" :allExercises="$allExercises" />
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
