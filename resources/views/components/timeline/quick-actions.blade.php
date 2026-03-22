@use('Illuminate\Support\Js')
@props([
    'templates',
    'userNow',
    'allExercises',
    'systemTemplates',
    'selectedTemplateId' => null,
])

@php
    $practiceTemplates = $templates->values();
@endphp

<div
    class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)] app-reveal"
    x-data="{ expandedTemplateId: {{ Js::from($selectedTemplateId) }}, showNewPractice: false }"
>
    <section class="app-panel rounded-2xl">
        <div class="p-6">
            <x-timeline.journal-form :userNow="$userNow" />
        </div>
    </section>

    <section class="space-y-4">
        <div class="app-panel rounded-2xl">
            <div class="p-6">
                <h3 class="app-section-title mb-4">Start Practice</h3>

                @if ($practiceTemplates->isEmpty())
                    <div class="rounded-xl border border-dashed border-white/15 bg-white/5 px-4 py-5 text-sm text-white/60">
                        No practice cards yet. Open <span class="font-semibold text-white">New Practice</span> to start from a recommendation or create one from scratch.
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($practiceTemplates as $template)
                            @php
                                $duration = $template->calculateDurationMinutes();
                            @endphp
                            <button
                                type="button"
                                @click="expandedTemplateId = expandedTemplateId === {{ $template->id }} ? null : {{ $template->id }}"
                                class="w-full rounded-xl border p-4 text-left transition-colors"
                                :class="expandedTemplateId === {{ $template->id }} ? 'border-emerald-400/40 bg-emerald-500/10' : 'border-white/10 bg-white/5 hover:bg-white/10'"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h4 class="font-semibold text-white">{{ $template->name }}</h4>
                                        @if ($template->description)
                                            <p class="mt-1 text-sm text-white/55">{{ $template->description }}</p>
                                        @endif
                                    </div>
                                    <span class="text-xs text-white/45">{{ $template->exercises->count() }} exercises</span>
                                </div>
                                <div class="mt-4 flex items-center justify-between gap-3 text-xs">
                                    <span class="text-emerald-200">Start Practice</span>
                                    <span class="text-white/45">
                                        @if ($duration > 0)
                                            ~{{ $duration }} min
                                        @else
                                            Open details
                                        @endif
                                    </span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="mt-4 flex justify-end">
                    <button
                        type="button"
                        @click="showNewPractice = !showNewPractice"
                        class="rounded-lg border border-emerald-400/30 bg-emerald-500/10 px-3 py-2 text-sm font-semibold text-emerald-200 transition-colors hover:bg-emerald-500/20"
                    >
                        New Practice
                    </button>
                </div>

                <div x-show="showNewPractice" x-transition class="mt-4 rounded-xl border border-white/10 bg-black/10 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-xs uppercase tracking-[0.24em] text-white/45">Recommendations</div>
                            <p class="mt-2 text-sm text-white/60">Starter templates stay tucked away here until you want a new practice.</p>
                        </div>
                        <button
                            type="button"
                            @click="showNewPractice = false"
                            class="text-sm text-white/45 transition-colors hover:text-white/70"
                        >
                            Close
                        </button>
                    </div>

                    @if ($systemTemplates->isNotEmpty())
                        <div class="mt-4 space-y-2">
                            @foreach ($systemTemplates as $starter)
                                <a
                                    href="{{ route('home', ['template' => $starter->id]) }}"
                                    class="flex items-center justify-between gap-3 rounded-lg border border-white/10 bg-white/5 px-3 py-3 transition-colors hover:bg-white/10"
                                >
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-white">{{ $starter->name }}</div>
                                        @if ($starter->description)
                                            <div class="mt-1 text-xs text-white/55 truncate">{{ $starter->description }}</div>
                                        @endif
                                    </div>
                                    <span class="shrink-0 text-xs text-white/45">{{ $starter->exercises->count() }} exercises</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-4 rounded-lg border border-dashed border-white/15 bg-white/5 px-3 py-4 text-sm text-white/55">
                            No starter templates are available right now.
                        </div>
                    @endif

                    <div class="mt-4 border-t border-white/10 pt-4">
                        <button
                            type="button"
                            @click="
                                $el.disabled = true;
                                $el.querySelector('span').textContent = 'Creating...';
                                fetch('{{ route('templates.store') }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': window.getCurrentCsrfToken(),
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
                                    $el.querySelector('span').textContent = 'Create Totally Custom Practice';
                                    alert('Failed to create template');
                                });
                            "
                            class="w-full rounded-lg border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-white/10 disabled:opacity-50"
                        >
                            <span>Create Totally Custom Practice</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($practiceTemplates as $template)
            <div x-show="expandedTemplateId === {{ $template->id }}" x-transition>
                <x-template-card :template="$template" :allExercises="$allExercises" />
            </div>
        @endforeach
    </section>
</div>
