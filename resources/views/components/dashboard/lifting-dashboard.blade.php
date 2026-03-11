@props([
    'isDisciplineLive' => false,
    'liftingDashboard' => [],
])

@php
    $movementGroups = [
        'push' => [
            'label' => 'Push Block',
            'summary' => 'Bench Press and Overhead Press',
            'categories' => [
                \App\Enums\LiftCategory::Bench->value,
                \App\Enums\LiftCategory::Overhead->value,
            ],
        ],
        'pull' => [
            'label' => 'Pull Block',
            'summary' => 'Deadlift and Barbell Row',
            'categories' => [
                \App\Enums\LiftCategory::Deadlift->value,
                \App\Enums\LiftCategory::Row->value,
            ],
        ],
        'legs' => [
            'label' => 'Legs Block',
            'summary' => 'Squat',
            'categories' => [
                \App\Enums\LiftCategory::Squat->value,
            ],
        ],
        'full_body' => [
            'label' => 'Full Body Block',
            'summary' => 'Power Clean',
            'categories' => [
                \App\Enums\LiftCategory::Clean->value,
            ],
        ],
    ];

    $patternLabels = [
        'push' => 'Push',
        'pull' => 'Pull',
        'legs' => 'Legs',
        'full_body' => 'Full Body',
    ];

    $patternClasses = [
        'push' => 'border-rose-400/30 bg-rose-500/10 text-rose-200',
        'pull' => 'border-emerald-400/30 bg-emerald-500/10 text-emerald-200',
        'legs' => 'border-amber-400/30 bg-amber-500/10 text-amber-200',
        'full_body' => 'border-sky-400/30 bg-sky-500/10 text-sky-200',
    ];

    $personalRecords = collect($liftingDashboard['personal_records'] ?? [])
        ->mapWithKeys(function ($record) use ($liftingDashboard) {
            $category = $record['category'] instanceof \App\Enums\LiftCategory
                ? $record['category']
                : \App\Enums\LiftCategory::from((string) $record['category']);

            $date = $record['date'] ?? null;

            if (is_string($date)) {
                $date = \Illuminate\Support\Carbon::parse($date);
            }

            return [
                $category->value => [
                    'category' => $category->value,
                    'label' => $record['label'] ?? $category->label(),
                    'movementPattern' => $record['movement_pattern'] ?? $category->movementPattern(),
                    'weightLbs' => $record['weight_lbs'] !== null ? (float) $record['weight_lbs'] : null,
                    'reps' => $record['reps'] !== null ? (int) $record['reps'] : null,
                    'date' => $date?->toIso8601String(),
                    'dateLabel' => $date?->format('M j, Y'),
                    'trend' => data_get($liftingDashboard, 'category_history.'.$category->value.'.trend', 'neutral'),
                ],
            ];
        });

    foreach (\App\Enums\LiftCategory::cases() as $category) {
        $personalRecords->put(
            $category->value,
            array_merge(
                [
                    'category' => $category->value,
                    'label' => $category->label(),
                    'movementPattern' => $category->movementPattern(),
                    'weightLbs' => null,
                    'reps' => null,
                    'date' => null,
                    'dateLabel' => null,
                    'trend' => 'neutral',
                ],
                $personalRecords->get($category->value, [])
            )
        );
    }

    $history = collect($liftingDashboard['category_history'] ?? [])
        ->mapWithKeys(function ($historyItem, $categoryValue) {
            $sessions = collect(data_get($historyItem, 'sessions', []))
                ->map(function ($sessionExercise) {
                    $loggedAt = data_get($sessionExercise, 'completed_at')
                        ?? data_get($sessionExercise, 'session.completed_at')
                        ?? data_get($sessionExercise, 'session.started_at')
                        ?? data_get($sessionExercise, 'created_at');

                    if (is_string($loggedAt)) {
                        $loggedAt = \Illuminate\Support\Carbon::parse($loggedAt);
                    }

                    return [
                        'weightLbs' => data_get($sessionExercise, 'weight_lbs') !== null ? (float) data_get($sessionExercise, 'weight_lbs') : null,
                        'repsCompleted' => data_get($sessionExercise, 'reps_completed') !== null ? (int) data_get($sessionExercise, 'reps_completed') : null,
                        'setsCompleted' => data_get($sessionExercise, 'sets_completed') !== null ? (int) data_get($sessionExercise, 'sets_completed') : null,
                        'loggedAt' => $loggedAt?->toIso8601String(),
                        'dateLabel' => $loggedAt?->format('M j, Y'),
                    ];
                })
                ->values()
                ->all();

            return [
                $categoryValue => [
                    'trend' => data_get($historyItem, 'trend', 'neutral'),
                    'sessions' => $sessions,
                ],
            ];
        })
        ->all();

    foreach (\App\Enums\LiftCategory::cases() as $category) {
        $history[$category->value] = $history[$category->value] ?? [
            'trend' => $personalRecords[$category->value]['trend'],
            'sessions' => [],
        ];
    }

    $recentActivity = collect($liftingDashboard['recent_sessions'] ?? [])
        ->map(function ($entry) {
            $category = \App\Enums\LiftCategory::tryFrom((string) data_get($entry, 'lift_category'));

            if (! $category) {
                return null;
            }

            $loggedAt = data_get($entry, 'completed_at')
                ?? data_get($entry, 'session.completed_at')
                ?? data_get($entry, 'session.started_at')
                ?? data_get($entry, 'created_at');

            if (is_string($loggedAt)) {
                $loggedAt = \Illuminate\Support\Carbon::parse($loggedAt);
            }

            return [
                'id' => (int) data_get($entry, 'id', 0),
                'category' => $category->value,
                'label' => $category->label(),
                'weightLbs' => data_get($entry, 'weight_lbs') !== null ? (float) data_get($entry, 'weight_lbs') : null,
                'repsCompleted' => data_get($entry, 'reps_completed') !== null ? (int) data_get($entry, 'reps_completed') : null,
                'setsCompleted' => data_get($entry, 'sets_completed') !== null ? (int) data_get($entry, 'sets_completed') : null,
                'isPersonalRecord' => (bool) data_get($entry, 'is_personal_record', false),
                'loggedAt' => $loggedAt?->toIso8601String(),
                'dateKey' => $loggedAt?->toDateString(),
                'dateLabel' => $loggedAt?->format('M j, Y') ?? 'Recent',
            ];
        })
        ->filter()
        ->groupBy('dateKey')
        ->map(function ($entries, $dateKey) {
            $firstEntry = $entries->first();

            return [
                'dateKey' => $dateKey ?: 'recent',
                'dateLabel' => $firstEntry['dateLabel'] ?? 'Recent',
                'entries' => $entries
                    ->map(fn (array $entry) => [
                        'id' => $entry['id'],
                        'category' => $entry['category'],
                        'label' => $entry['label'],
                        'weightLbs' => $entry['weightLbs'],
                        'repsCompleted' => $entry['repsCompleted'],
                        'setsCompleted' => $entry['setsCompleted'],
                        'isPersonalRecord' => $entry['isPersonalRecord'],
                        'loggedAt' => $entry['loggedAt'],
                    ])
                    ->values()
                    ->all(),
            ];
        })
        ->values()
        ->all();

    $initialForms = $personalRecords
        ->mapWithKeys(function (array $record, string $category) {
            $suggestedWeight = $record['weightLbs'] !== null
                ? (int) round($record['weightLbs'] * 0.8)
                : '';

            return [
                $category => [
                    'weight_lbs' => $suggestedWeight,
                    'reps_completed' => 5,
                    'sets_completed' => 3,
                ],
            ];
        })
        ->all();

    $initialOpenBlocks = collect(array_keys($movementGroups))
        ->mapWithKeys(fn (string $key) => [$key => true])
        ->all();
@endphp

<div class="lifting-dashboard">
    @if (! $isDisciplineLive)
        <div class="app-panel p-6 text-center sm:rounded-2xl">
            <p class="text-white/60">Lifting is coming soon.</p>
        </div>
    @else
        <div
            class="space-y-6"
            x-data="{
                personalRecords: @js($personalRecords->all()),
                history: @js($history),
                recentActivity: @js($recentActivity),
                openBlocks: @js($initialOpenBlocks),
                openForms: {},
                forms: @js($initialForms),
                submitting: {},
                errors: {},
                flashStates: {},
                openActivity: false,
                logUrl: @js(route('lifting.log-set')),
                init() {
                    window.addEventListener('lifting-set-logged', (event) => this.applyLoggedSet(event.detail));
                },
                toggleBlock(block) {
                    this.openBlocks[block] = ! this.openBlocks[block];
                },
                toggleForm(category) {
                    if (this.openForms[category]) {
                        this.openForms[category] = false;
                        this.errors[category] = {};
                        return;
                    }

                    this.seedForm(category);
                    this.errors[category] = {};
                    this.openForms[category] = true;
                },
                seedForm(category) {
                    if (this.forms[category]) {
                        return;
                    }

                    const suggestedWeight = this.suggestedWorkingWeight(category);

                    this.forms[category] = {
                        weight_lbs: suggestedWeight === null ? '' : suggestedWeight,
                        reps_completed: 5,
                        sets_completed: 3,
                    };
                },
                suggestedWorkingWeight(category) {
                    const record = this.personalRecords[category] ?? null;

                    if (! record || record.weightLbs === null) {
                        return null;
                    }

                    return Math.round(Number(record.weightLbs) * 0.8);
                },
                workingWeightLabel(category) {
                    const suggested = this.suggestedWorkingWeight(category);

                    if (suggested === null) {
                        return 'Record your first set';
                    }

                    return `${this.formatWeight(suggested)} lb`;
                },
                lastSessionLabel(category) {
                    const lastSession = this.history[category]?.sessions?.[0] ?? null;

                    if (! lastSession || lastSession.weightLbs === null || lastSession.repsCompleted === null) {
                        return 'No data yet - record your first set';
                    }

                    return `Last: ${this.formatWeight(lastSession.weightLbs)} lb x ${lastSession.repsCompleted}`;
                },
                weightLabel(category) {
                    const record = this.personalRecords[category] ?? null;

                    if (! record || record.weightLbs === null) {
                        return '--';
                    }

                    return `${this.formatWeight(record.weightLbs)} lb`;
                },
                repsLabel(category) {
                    const reps = this.personalRecords[category]?.reps;

                    if (! reps) {
                        return 'Reps at PR: --';
                    }

                    return `Reps at PR: ${reps}`;
                },
                dateLabel(category) {
                    return this.personalRecords[category]?.dateLabel ?? '--';
                },
                trendFor(category) {
                    return this.personalRecords[category]?.trend ?? 'neutral';
                },
                trendArrow(category) {
                    return {
                        up: '↑',
                        down: '↓',
                        neutral: '→',
                    }[this.trendFor(category)] ?? '→';
                },
                trendLabel(category) {
                    return {
                        up: 'Rising',
                        down: 'Pulling back',
                        neutral: 'Steady',
                    }[this.trendFor(category)] ?? 'Steady';
                },
                trendClasses(category) {
                    return {
                        up: 'text-emerald-300',
                        down: 'text-amber-300',
                        neutral: 'text-white/45',
                    }[this.trendFor(category)] ?? 'text-white/45';
                },
                flashClass(category) {
                    return this.flashStates[category]
                        ? 'ring-1 ring-emerald-400/60 shadow-[0_0_0_1px_rgba(52,211,153,0.28),0_0_28px_rgba(16,185,129,0.18)]'
                        : '';
                },
                fieldError(category, field) {
                    const categoryErrors = this.errors[category] ?? {};

                    if (! Array.isArray(categoryErrors[field]) || categoryErrors[field].length === 0) {
                        return null;
                    }

                    return categoryErrors[field][0];
                },
                async submitLog(category) {
                    this.seedForm(category);
                    this.submitting[category] = true;
                    this.errors[category] = {};

                    const form = this.forms[category];
                    const payload = {
                        lift_category: category,
                        weight_lbs: form.weight_lbs,
                        reps_completed: form.reps_completed,
                        sets_completed: form.sets_completed,
                    };

                    try {
                        const response = await fetch(this.logUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                            },
                            body: JSON.stringify(payload),
                        });

                        let data = {};

                        try {
                            data = await response.json();
                        } catch (error) {
                            data = {};
                        }

                        if (! response.ok) {
                            this.errors[category] = data.errors ?? {
                                general: ['Unable to log this set right now.'],
                            };
                            return;
                        }

                        window.dispatchEvent(new CustomEvent('lifting-set-logged', {
                            detail: {
                                category,
                                weightLbs: Number(data.weight_lbs),
                                repsCompleted: Number(data.reps_completed),
                                setsCompleted: Number(form.sets_completed || 1),
                                isPersonalRecord: Boolean(data.is_personal_record),
                                loggedAt: new Date().toISOString(),
                            },
                        }));

                        this.forms[category] = {
                            weight_lbs: this.suggestedWorkingWeight(category) ?? '',
                            reps_completed: 5,
                            sets_completed: 3,
                        };
                        this.openForms[category] = false;
                    } finally {
                        this.submitting[category] = false;
                    }
                },
                applyLoggedSet(detail) {
                    const category = detail.category;
                    const weightLbs = Number(detail.weightLbs);
                    const repsCompleted = Number(detail.repsCompleted);
                    const setsCompleted = Number(detail.setsCompleted || 1);
                    const loggedAt = detail.loggedAt ?? new Date().toISOString();
                    const previousSession = this.history[category]?.sessions?.[0] ?? null;

                    if (! this.history[category]) {
                        this.history[category] = {
                            trend: 'neutral',
                            sessions: [],
                        };
                    }

                    this.history[category].sessions.unshift({
                        weightLbs,
                        repsCompleted,
                        setsCompleted,
                        loggedAt,
                        dateLabel: this.formatDate(loggedAt),
                    });
                    this.history[category].sessions = this.history[category].sessions.slice(0, 10);

                    if (detail.isPersonalRecord) {
                        this.personalRecords[category].weightLbs = weightLbs;
                        this.personalRecords[category].reps = repsCompleted;
                        this.personalRecords[category].date = loggedAt;
                        this.personalRecords[category].dateLabel = this.formatDate(loggedAt);
                        this.personalRecords[category].trend = previousSession
                            ? this.compareTrend(weightLbs, previousSession.weightLbs)
                            : 'up';
                        this.triggerFlash(category);
                    } else {
                        this.personalRecords[category].trend = previousSession
                            ? this.compareTrend(weightLbs, previousSession.weightLbs)
                            : this.personalRecords[category].trend;
                    }

                    this.history[category].trend = this.personalRecords[category].trend;
                    this.prependRecentActivity({
                        category,
                        label: this.personalRecords[category].label,
                        weightLbs,
                        repsCompleted,
                        setsCompleted,
                        isPersonalRecord: Boolean(detail.isPersonalRecord),
                        loggedAt,
                    });
                },
                prependRecentActivity(entry) {
                    const dateKey = this.dateKey(entry.loggedAt);
                    const existingGroup = this.recentActivity.find((group) => group.dateKey === dateKey);
                    const normalizedEntry = {
                        id: Date.now(),
                        category: entry.category,
                        label: entry.label,
                        weightLbs: entry.weightLbs,
                        repsCompleted: entry.repsCompleted,
                        setsCompleted: entry.setsCompleted,
                        isPersonalRecord: entry.isPersonalRecord,
                        loggedAt: entry.loggedAt,
                    };

                    if (existingGroup) {
                        existingGroup.entries.unshift(normalizedEntry);
                        return;
                    }

                    this.recentActivity.unshift({
                        dateKey,
                        dateLabel: this.formatDate(entry.loggedAt),
                        entries: [normalizedEntry],
                    });
                },
                triggerFlash(category) {
                    this.flashStates[category] = true;

                    setTimeout(() => {
                        this.flashStates[category] = false;
                    }, 1400);
                },
                compareTrend(currentWeight, previousWeight) {
                    const current = Number(currentWeight);
                    const previous = Number(previousWeight);

                    if (! Number.isFinite(previous)) {
                        return 'neutral';
                    }

                    if (current > previous) {
                        return 'up';
                    }

                    if (current < previous) {
                        return 'down';
                    }

                    return 'neutral';
                },
                patternLabelFor(category) {
                    const pattern = this.personalRecords[category]?.movementPattern ?? 'full_body';

                    return {
                        push: 'Push',
                        pull: 'Pull',
                        legs: 'Legs',
                        full_body: 'Full Body',
                    }[pattern] ?? 'Full Body';
                },
                activitySummary(entry) {
                    const setCount = entry.setsCompleted ? `${entry.setsCompleted} ${entry.setsCompleted === 1 ? 'set' : 'sets'}` : 'set';
                    const repCount = entry.repsCompleted ?? '--';
                    const weight = entry.weightLbs === null ? '--' : `${this.formatWeight(entry.weightLbs)} lb`;

                    return `${weight} x ${repCount}, ${setCount}`;
                },
                dateKey(value) {
                    if (! value) {
                        return 'recent';
                    }

                    return new Date(value).toISOString().slice(0, 10);
                },
                formatDate(value) {
                    if (! value) {
                        return '--';
                    }

                    return new Intl.DateTimeFormat('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                    }).format(new Date(value));
                },
                formatWeight(value) {
                    const weight = Number(value);

                    if (! Number.isFinite(weight)) {
                        return '--';
                    }

                    return weight
                        .toFixed(2)
                        .replace(/\\.00$/, '')
                        .replace(/(\\.\\d)0$/, '$1');
                },
            }"
        >
            <section class="app-panel p-6 sm:rounded-2xl sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-xs uppercase tracking-[0.24em] text-white/45">Lifting</div>
                        <h3 class="mt-2 text-2xl font-semibold text-white">Personal Records</h3>
                        <p class="mt-2 max-w-2xl text-sm text-white/60">A clear view of current reference points across the main lifts.</p>
                    </div>
                    <div class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-white/55">
                        Observed from logged practice
                    </div>
                </div>

                <div class="mt-6 space-y-6">
                    @foreach ($movementGroups as $pattern => $group)
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <span class="app-chip {{ $patternClasses[$pattern] }}">{{ $patternLabels[$pattern] }}</span>
                                <p class="text-sm text-white/45">{{ $group['summary'] }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($group['categories'] as $category)
                                    @php
                                        $record = $personalRecords[$category];
                                    @endphp
                                    <article
                                        class="app-card app-card--stack rounded-2xl p-5 transition-all duration-300"
                                        :class="flashClass('{{ $category }}')"
                                    >
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/40">Lift</div>
                                                <h4 class="mt-2 text-lg font-semibold text-white">{{ $record['label'] }}</h4>
                                            </div>
                                            <span class="app-chip {{ $patternClasses[$pattern] }}">{{ $patternLabels[$pattern] }}</span>
                                        </div>

                                        <div class="mt-6 flex items-end justify-between gap-4">
                                            <div>
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/40">Current PR</div>
                                                <div class="mt-2 text-3xl font-semibold text-white" x-text="weightLabel('{{ $category }}')"></div>
                                                <div class="mt-1 text-sm text-white/55" x-text="repsLabel('{{ $category }}')"></div>
                                            </div>
                                            <div class="text-right" :class="trendClasses('{{ $category }}')">
                                                <div class="text-2xl leading-none" x-text="trendArrow('{{ $category }}')"></div>
                                                <div class="mt-1 text-xs uppercase tracking-[0.24em]" x-text="trendLabel('{{ $category }}')"></div>
                                            </div>
                                        </div>

                                        <div class="mt-5 border-t border-white/10 pt-4">
                                            <div class="text-xs uppercase tracking-[0.24em] text-white/40">Recorded</div>
                                            <div class="mt-1 text-sm text-white/70" x-text="dateLabel('{{ $category }}')"></div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="app-panel p-6 sm:rounded-2xl sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-xs uppercase tracking-[0.24em] text-white/45">Practice Suggestions</div>
                        <h3 class="mt-2 text-2xl font-semibold text-white">Practice Blocks</h3>
                        <p class="mt-2 max-w-2xl text-sm text-white/60">Each block uses the current record as a reference point and keeps the work simple.</p>
                    </div>
                    <div class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-white/55">
                        Strength focus: 3 x 5
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @foreach ($movementGroups as $pattern => $group)
                        <section class="app-card app-card--nested rounded-2xl p-5">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-4 text-left"
                                @click="toggleBlock('{{ $pattern }}')"
                            >
                                <div>
                                    <div class="flex items-center gap-3">
                                        <span class="app-chip {{ $patternClasses[$pattern] }}">{{ $group['label'] }}</span>
                                        <span class="text-xs uppercase tracking-[0.24em] text-white/40">{{ count($group['categories']) }} lifts</span>
                                    </div>
                                    <p class="mt-3 text-sm text-white/60">{{ $group['summary'] }}. Rest 3-5 min between heavy sets.</p>
                                </div>
                                <div class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/65 transition-transform duration-200" :class="openBlocks['{{ $pattern }}'] ? 'rotate-180' : ''">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9l6 6 6-6" />
                                    </svg>
                                </div>
                            </button>

                            <div x-show="openBlocks['{{ $pattern }}']" x-transition class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                @foreach ($group['categories'] as $category)
                                    @php
                                        $record = $personalRecords[$category];
                                    @endphp
                                    <article
                                        class="app-card app-card--stack rounded-2xl p-5 transition-all duration-300"
                                        :class="flashClass('{{ $category }}')"
                                    >
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <h4 class="text-lg font-semibold text-white">{{ $record['label'] }}</h4>
                                                <p class="mt-2 text-sm text-white/60" x-text="lastSessionLabel('{{ $category }}')"></p>
                                            </div>
                                            <button
                                                type="button"
                                                class="app-btn app-btn-secondary rounded-lg px-4 py-2 text-sm"
                                                @click="toggleForm('{{ $category }}')"
                                            >
                                                Log Set
                                            </button>
                                        </div>

                                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                            <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/40">Suggested Weight</div>
                                                <div class="mt-2 text-xl font-semibold text-white" x-text="workingWeightLabel('{{ $category }}')"></div>
                                            </div>
                                            <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                                                <div class="text-xs uppercase tracking-[0.24em] text-white/40">Sets x Reps</div>
                                                <div class="mt-2 text-xl font-semibold text-white">3 x 5</div>
                                            </div>
                                        </div>

                                        <p class="mt-4 text-sm text-white/55">Rest 3-5 min between heavy sets.</p>

                                        <form
                                            x-show="openForms['{{ $category }}']"
                                            x-transition
                                            @submit.prevent="submitLog('{{ $category }}')"
                                            class="mt-5 space-y-4 rounded-2xl border border-white/10 bg-black/20 p-4"
                                        >
                                            <div class="grid gap-4 sm:grid-cols-3">
                                                <div>
                                                    <label class="mb-2 block text-xs uppercase tracking-[0.24em] text-white/40">Weight (lb)</label>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        step="0.5"
                                                        x-model="forms['{{ $category }}'].weight_lbs"
                                                        class="w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white placeholder:text-white/30"
                                                    >
                                                    <p x-show="fieldError('{{ $category }}', 'weight_lbs')" x-text="fieldError('{{ $category }}', 'weight_lbs')" class="mt-2 text-xs text-rose-300"></p>
                                                </div>

                                                <div>
                                                    <label class="mb-2 block text-xs uppercase tracking-[0.24em] text-white/40">Reps</label>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        step="1"
                                                        x-model="forms['{{ $category }}'].reps_completed"
                                                        class="w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white placeholder:text-white/30"
                                                    >
                                                    <p x-show="fieldError('{{ $category }}', 'reps_completed')" x-text="fieldError('{{ $category }}', 'reps_completed')" class="mt-2 text-xs text-rose-300"></p>
                                                </div>

                                                <div>
                                                    <label class="mb-2 block text-xs uppercase tracking-[0.24em] text-white/40">Sets</label>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        step="1"
                                                        x-model="forms['{{ $category }}'].sets_completed"
                                                        class="w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white placeholder:text-white/30"
                                                    >
                                                    <p x-show="fieldError('{{ $category }}', 'sets_completed')" x-text="fieldError('{{ $category }}', 'sets_completed')" class="mt-2 text-xs text-rose-300"></p>
                                                </div>
                                            </div>

                                            <p x-show="fieldError('{{ $category }}', 'general')" x-text="fieldError('{{ $category }}', 'general')" class="text-sm text-rose-300"></p>

                                            <div class="flex flex-wrap items-center gap-3">
                                                <button
                                                    type="submit"
                                                    class="app-btn app-btn-primary rounded-lg px-4 py-2 text-sm"
                                                    :disabled="submitting['{{ $category }}']"
                                                >
                                                    <span x-show="!submitting['{{ $category }}']">Log Set</span>
                                                    <span x-show="submitting['{{ $category }}']">Logging...</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="app-btn app-btn-secondary rounded-lg px-4 py-2 text-sm"
                                                    @click="toggleForm('{{ $category }}')"
                                                >
                                                    Close
                                                </button>
                                            </div>
                                        </form>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </section>

            <section class="app-panel p-6 sm:rounded-2xl sm:p-8">
                <button type="button" class="flex w-full items-center justify-between gap-4 text-left" @click="openActivity = ! openActivity">
                    <div>
                        <div class="text-xs uppercase tracking-[0.24em] text-white/45">Recent Activity</div>
                        <h3 class="mt-2 text-2xl font-semibold text-white">Last 14 Days</h3>
                        <p class="mt-2 text-sm text-white/60">Logged lifting entries, grouped by date.</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/65 transition-transform duration-200" :class="openActivity ? 'rotate-180' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9l6 6 6-6" />
                        </svg>
                    </div>
                </button>

                <div x-show="openActivity" x-transition class="mt-6 space-y-4">
                    <template x-if="recentActivity.length === 0">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-5 text-sm text-white/60">
                            No lifting entries observed in the last 14 days.
                        </div>
                    </template>

                    <template x-for="day in recentActivity" :key="day.dateKey">
                        <section class="app-card app-card--nested rounded-2xl p-5">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs uppercase tracking-[0.24em] text-white/40">Date</div>
                                    <h4 class="mt-1 text-lg font-semibold text-white" x-text="day.dateLabel"></h4>
                                </div>
                                <div class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-white/55" x-text="`${day.entries.length} entries`"></div>
                            </div>

                            <div class="mt-4 space-y-3">
                                <template x-for="entry in day.entries" :key="`${day.dateKey}-${entry.category}-${entry.loggedAt}-${entry.weightLbs}`">
                                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h5 class="text-sm font-semibold text-white" x-text="entry.label"></h5>
                                                    <span
                                                        x-show="entry.isPersonalRecord"
                                                        class="rounded-full border border-emerald-400/30 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-200"
                                                    >
                                                        PR
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-sm text-white/60" x-text="activitySummary(entry)"></p>
                                            </div>
                                            <div class="text-xs uppercase tracking-[0.24em] text-white/45" x-text="patternLabelFor(entry.category)"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </section>
                    </template>
                </div>
            </section>
        </div>
    @endif
</div>
