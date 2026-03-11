<x-app-layout>
    <div class="min-h-full xl:h-full overflow-visible xl:overflow-hidden flex flex-col go-screen app-reveal" :class="isResting ? 'go-screen--rest' : ''">
        <div class="w-full flex-1 flex items-start xl:items-center justify-center min-h-0">
            <div class="w-full min-h-full xl:h-full flex items-start xl:items-center justify-center">
                <div class="w-full min-h-full xl:h-full text-white">
                    @if ($practiceItems->isEmpty())
                        <p class="text-white/60">No exercises in this template yet.</p>
                    @else
                        <div
                            x-data="workoutTimer({ sessionId: {{ $session->id }}, items: @js($practiceItems) })"
                            x-effect="$dispatch('workout-state-changed', { running: state === 'running' })"
                            class="w-full h-full"
                        >
                            <div x-show="state === 'completed'" class="text-center py-6 flex flex-col items-center justify-between h-full overflow-hidden">
                                <div class="shrink-0">
                                    <div class="mb-4">
                                        <svg class="w-16 h-16 mx-auto text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <h2 class="text-4xl md:text-6xl font-bold text-white mb-2">Practice Complete</h2>
                                    <p class="text-2xl md:text-4xl text-white/70">Total time: <span x-text="formatTime(totalElapsedSeconds)"></span></p>
                                </div>

                                <div class="max-w-4xl mx-auto flex-1 min-h-0 overflow-auto my-4 w-full px-4">
                                    <div class="app-panel rounded-2xl p-4 space-y-4">
                                        <div>
                                            <h3 class="text-xl font-semibold text-white mb-3 flex items-center justify-center gap-2">
                                                <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span>Completed Blocks (<span x-text="completedExercises.length"></span>)</span>
                                            </h3>
                                            <div class="space-y-2">
                                                <template x-for="exercise in completedExercises" :key="`${exercise.order}-${exercise.name}`">
                                                    <div class="text-white/80 app-card app-card--nested rounded-lg p-2 text-base">
                                                        <span x-text="exercise.name"></span>
                                                        <span class="text-white/45" x-show="exercise.linked_name" x-text="` · ${exercise.linked_name}`"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-4 justify-center flex-wrap shrink-0">
                                    <a href="{{ route('dashboard') }}">
                                        <button type="button" class="px-10 py-4 bg-emerald-500 text-white rounded-xl text-xl font-semibold hover:bg-emerald-600 transition-colors go-cta">
                                            Back to Dashboard
                                        </button>
                                    </a>
                                    <a href="{{ $restartUrl }}">
                                        <button type="button" class="px-10 py-4 bg-white/10 text-white rounded-xl text-xl font-semibold hover:bg-white/20 transition-colors">
                                            Do it again
                                        </button>
                                    </a>
                                </div>
                            </div>

                            <div x-show="state !== 'completed'" class="w-full min-h-full xl:h-full px-4 py-4 sm:px-6 sm:py-6 md:px-10 md:py-8 grid grid-cols-1 xl:grid-cols-[minmax(0,1.7fr)_minmax(20rem,0.9fr)] gap-4 sm:gap-6 items-start xl:items-stretch">
                                <div class="app-panel rounded-2xl p-4 sm:p-6 flex flex-col gap-6 xl:gap-0 xl:justify-between min-h-0">
                                    <div class="flex flex-wrap items-start sm:items-center justify-between gap-3">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <span class="inline-flex items-center rounded-full border px-4 py-1.5 text-sm font-semibold"
                                                :class="isResting ? 'border-cyan-400/30 bg-cyan-500/10 text-cyan-200' : 'border-emerald-400/30 bg-emerald-500/10 text-emerald-200'">
                                                <span x-text="isResting ? 'Rest' : 'Block'"></span>
                                                <span class="mx-1 text-white/30">·</span>
                                                <span x-text="`${currentItemIndex + 1} / ${items.length}`"></span>
                                            </span>
                                            <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-sm font-semibold text-white/70">
                                                Repeat <span class="mx-1 text-white/30">·</span> <span x-text="`${currentRepeat} / ${Math.max(1, currentItem?.repeats || 1)}`"></span>
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="toggleAudio()"
                                            class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-sm font-medium text-white/70 transition-colors hover:bg-white/10"
                                            :class="audioEnabled ? 'text-emerald-200 border-emerald-400/30 bg-emerald-500/10' : ''"
                                        >
                                            <span x-text="audioEnabled ? 'Audio On' : 'Audio Off'"></span>
                                        </button>
                                    </div>

                                    <div class="flex flex-col items-center text-center py-2 sm:py-4 xl:flex-1 xl:justify-center min-h-0">
                                        <div class="text-[4rem] sm:text-[4.75rem] md:text-[7rem] font-bold text-white tracking-tight leading-none" x-text="formatTime(displaySeconds)"></div>
                                        <div class="mt-2 text-sm uppercase tracking-[0.35em] text-white/45" x-text="timerCaption"></div>

                                        <div class="mt-6 sm:mt-8 w-full max-w-3xl space-y-2 sm:space-y-3 px-1">
                                            <div class="text-3xl sm:text-4xl md:text-5xl font-bold text-white break-words" x-text="currentItem?.name"></div>
                                            <div class="text-lg sm:text-xl text-cyan-200 break-words" x-show="currentItem?.linked_name" x-text="currentItem?.linked_name"></div>
                                            <div class="text-base sm:text-lg text-white/65 break-words" x-show="currentItem?.description" x-text="currentItem?.description"></div>
                                        </div>

                                        <div class="mt-5 sm:mt-6 flex flex-wrap justify-center gap-3 text-sm">
                                            <span x-show="currentItem?.distance_label" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-white/75" x-text="currentItem?.distance_label"></span>
                                            <span x-show="currentItem?.target_cue" class="rounded-full border border-cyan-400/20 bg-cyan-500/10 px-4 py-2 text-cyan-200" x-text="currentItem?.target_cue"></span>
                                            <span x-show="!isResting && currentItem?.completion_mode === 'manual'" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-white/75">Manual complete</span>
                                            <span x-show="!isResting && currentItem?.completion_mode === 'timed'" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-white/75" x-text="`${currentItem?.duration_seconds || 0}s`"></span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap justify-center gap-3 sm:gap-4">
                                        <button x-show="state === 'ready'" @click="start"
                                            class="px-8 py-3 sm:px-10 sm:py-4 bg-emerald-500 text-white rounded-xl text-lg sm:text-xl md:text-2xl font-semibold hover:bg-emerald-600 transition-colors go-cta">
                                            Start Practice
                                        </button>

                                        <button x-show="state === 'running'" @click="pause"
                                            class="px-8 py-3 sm:px-10 sm:py-4 bg-cyan-500 text-white rounded-xl text-lg sm:text-xl md:text-2xl font-semibold hover:bg-cyan-600 transition-colors">
                                            Pause
                                        </button>

                                        <button x-show="state === 'paused'" @click="resume"
                                            class="px-8 py-3 sm:px-10 sm:py-4 bg-emerald-500 text-white rounded-xl text-lg sm:text-xl md:text-2xl font-semibold hover:bg-emerald-600 transition-colors">
                                            Resume
                                        </button>

                                        <button x-show="state === 'running' || state === 'paused'" @click="next"
                                            class="px-8 py-3 sm:px-10 sm:py-4 bg-white/10 text-white rounded-xl text-lg sm:text-xl md:text-2xl font-semibold hover:bg-white/20 transition-colors">
                                            <span x-text="nextButtonLabel"></span>
                                        </button>
                                    </div>

                                    <div x-show="state !== 'completed'" class="hidden md:flex gap-4 justify-center text-sm text-white/40 mt-4">
                                        <span x-show="state === 'ready'"><kbd class="px-1.5 py-0.5 bg-white/10 rounded text-xs">Enter</kbd> to start</span>
                                        <span x-show="state === 'running' || state === 'paused'"><kbd class="px-1.5 py-0.5 bg-white/10 rounded text-xs">Space</kbd> pause/resume</span>
                                        <span x-show="state === 'running' || state === 'paused'"><kbd class="px-1.5 py-0.5 bg-white/10 rounded text-xs">Enter</kbd> <span x-text="nextButtonLabel.toLowerCase()"></span></span>
                                    </div>
                                </div>

                                <div class="space-y-4 sm:space-y-6 xl:min-h-0 xl:overflow-auto">
                                    <div class="app-panel rounded-2xl p-4 sm:p-5">
                                        <div class="text-xs uppercase tracking-[0.24em] text-white/45">Current Block</div>
                                        <div class="mt-3 space-y-3 text-sm">
                                            <template x-if="currentItem?.notes">
                                                <div>
                                                    <div class="text-white/45">Block Notes</div>
                                                    <p class="mt-1 text-white/75 whitespace-pre-line" x-text="currentItem?.notes"></p>
                                                </div>
                                            </template>
                                            <template x-if="currentItem?.setup_text">
                                                <div>
                                                    <div class="text-white/45">Setup</div>
                                                    <p class="mt-1 text-white/75 whitespace-pre-line" x-text="currentItem?.setup_text"></p>
                                                </div>
                                            </template>
                                            <template x-if="currentItem?.instructions">
                                                <div>
                                                    <div class="text-white/45">Instructions</div>
                                                    <p class="mt-1 text-white/75 whitespace-pre-line" x-text="currentItem?.instructions"></p>
                                                </div>
                                            </template>
                                            <template x-if="currentItem?.field_layout_notes">
                                                <div>
                                                    <div class="text-white/45">Field Layout</div>
                                                    <p class="mt-1 text-white/75 whitespace-pre-line" x-text="currentItem?.field_layout_notes"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="app-panel rounded-2xl p-4 sm:p-5">
                                        <div class="text-xs uppercase tracking-[0.24em] text-white/45">Next Segment</div>
                                        <template x-if="nextSegment">
                                            <div class="mt-3">
                                                <div class="text-lg font-semibold text-white" x-text="nextSegment.label"></div>
                                                <div class="mt-1 text-sm text-white/60" x-show="nextSegment.detail" x-text="nextSegment.detail"></div>
                                            </div>
                                        </template>
                                        <template x-if="!nextSegment">
                                            <p class="mt-3 text-sm text-white/60">This is the last segment.</p>
                                        </template>
                                    </div>

                                    <div class="app-panel rounded-2xl p-4 sm:p-5">
                                        <div class="text-xs uppercase tracking-[0.24em] text-white/45">Session Flow</div>
                                        <div class="mt-3 space-y-2">
                                            <template x-for="(item, index) in items" :key="`${item.order}-${item.name}`">
                                                <div class="rounded-lg border px-3 py-3 text-sm"
                                                    :class="index === currentItemIndex ? 'border-emerald-400/30 bg-emerald-500/10 text-white' : 'border-white/10 bg-white/5 text-white/70'">
                                                    <div class="font-semibold" x-text="item.name"></div>
                                                    <div class="mt-1 text-xs text-white/50" x-show="item.linked_name" x-text="item.linked_name"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
