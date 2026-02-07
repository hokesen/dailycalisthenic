<x-app-layout>
    <div class="h-full overflow-hidden flex flex-col go-screen">
        <div class="w-full flex-1 flex items-center justify-center min-h-0">
            <div class="w-full h-full flex items-center justify-center">
                <div class="w-full h-full text-white">
                    @if ($exercises->isEmpty())
                        <p class="text-white/60">No exercises in this template yet.</p>
                    @else
                        <div
                            x-data="workoutTimer({ sessionId: {{ $session->id }}, exercises: @js($exercisesData) })"
                            x-effect="$dispatch('workout-state-changed', { running: state === 'running' })"
                            class="w-full h-full">

                            <!-- Practice Complete Screen -->
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

                                <!-- Exercise Summary -->
                                <div class="max-w-4xl mx-auto flex-1 min-h-0 overflow-auto my-4 w-full px-4">
                                    <div class="app-panel rounded-2xl p-4 space-y-4">
                                        <!-- Completed Exercises -->
                                        <div>
                                            <h3 class="text-xl font-semibold text-white mb-3 flex items-center justify-center gap-2">
                                                <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span>Completed (<span x-text="completedExercises.length"></span>)</span>
                                            </h3>
                                            <div class="space-y-2">
                                                <template x-for="exercise in completedExercises" :key="exercise.id">
                                                    <div class="text-white/80 app-card app-card--nested rounded-lg p-2 text-base">
                                                        <span x-text="exercise.name"></span>
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
                                    <a href="{{ route('go.index', ['template' => $template->id]) }}">
                                        <button type="button" class="px-10 py-4 bg-white/10 text-white rounded-xl text-xl font-semibold hover:bg-white/20 transition-colors">
                                            Do it again
                                        </button>
                                    </a>
                                </div>
                            </div>

                            <!-- Main Timer Screen -->
                            <div x-show="state !== 'completed'" class="h-full w-full px-10 py-8 md:px-12 md:py-10 grid grid-cols-1 md:grid-cols-12 gap-6 items-center relative">
                                <!-- Edge Progress Frame -->
                                <div class="pointer-events-none absolute inset-1 md:inset-2" :class="state !== 'running' ? 'edge-pulse-paused' : ''">
                                    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                                        <defs>
                                            <filter id="edgeGlow" x="-20%" y="-20%" width="140%" height="140%">
                                                <feGaussianBlur stdDeviation="0.8" result="blur" />
                                                <feColorMatrix
                                                    in="blur"
                                                    type="matrix"
                                                    values="0 0 0 0 0.20
                                                            0 0 0 0 0.85
                                                            0 0 0 0 0.65
                                                            0 0 0 0.9 0"
                                                />
                                            </filter>
                                            <mask id="edgeProgressMask">
                                                <rect x="2" y="2" width="96" height="96" rx="6" ry="6"
                                                    fill="none"
                                                    stroke="white"
                                                    stroke-width="1.6"
                                                    pathLength="100"
                                                    stroke-dasharray="100"
                                                    :stroke-dashoffset="100 * (1 - progress)" />
                                            </mask>
                                        </defs>
                                        <rect x="2" y="2" width="96" height="96" rx="6" ry="6"
                                            fill="none"
                                            stroke="rgba(52, 211, 153, 0.9)"
                                            stroke-width="1.4"
                                            pathLength="100"
                                            stroke-dasharray="100"
                                            :stroke-dashoffset="state === 'ready' ? 100 : 100 * (1 - progress)"
                                            stroke-linecap="round"
                                            filter="url(#edgeGlow)"
                                            :style="state === 'ready' ? 'opacity: 0;' : 'opacity: 1;'" />
                                        <rect x="2" y="2" width="96" height="96" rx="6" ry="6"
                                            fill="none"
                                            stroke="rgba(94, 234, 212, 0.7)"
                                            stroke-width="1.2"
                                            pathLength="100"
                                            stroke-dasharray="4 18"
                                            stroke-linecap="round"
                                            :class="state === 'running' && progress > 0.02 ? 'edge-pulse' : ''"
                                            mask="url(#edgeProgressMask)"
                                            :style="state === 'running' && progress > 0.02 ? 'opacity: 1;' : 'opacity: 0;'" />
                                    </svg>
                                </div>
                                <!-- Main Content -->
                                <div class="md:col-span-10 flex flex-col justify-between items-center h-full w-full py-2">
                                    <!-- Status and Progress -->
                                    <div class="text-center shrink-0">
                                        <div>
                                            <span class="inline-block px-7 py-2 rounded-full text-xl md:text-2xl font-semibold border go-pill"
                                                :class="isResting ? 'bg-cyan-500/15 text-cyan-200 border-cyan-400/30' : 'bg-emerald-500/15 text-emerald-200 border-emerald-400/30'">
                                                <span x-text="isResting ? 'Rest Period' : 'Exercise'"></span>
                                                <span x-text="' ' + (currentExerciseIndex + 1) + ' of ' + exercises.length"></span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Circular Progress Timer -->
                                    <div class="flex justify-center items-center flex-1 min-h-0">
                                        <div class="relative w-full max-w-[min(55vh,520px)] aspect-square go-float">
                                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                <div class="text-[5.5rem] md:text-[7.5rem] font-bold text-white tracking-tight" x-text="formatTime(timeRemaining)"></div>
                                                <div class="text-2xl md:text-3xl text-white/60 mt-2 uppercase tracking-[0.25em]" x-text="isResting ? 'Rest' : ''"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Exercise Info -->
                                    <div class="text-center space-y-2 shrink-0" x-data="{ showInstructions: false }">
                                        <h3 class="text-4xl md:text-6xl font-bold text-white" x-text="currentExercise?.name"></h3>
                                        <div x-show="currentExercise?.description" class="text-xl md:text-2xl text-white/70" x-text="currentExercise?.description"></div>
                                        <div class="flex gap-5 justify-center text-lg md:text-xl text-white/55 flex-wrap">
                                            <span x-show="currentExercise?.sets && currentExercise?.reps">
                                                <span x-text="currentExercise?.sets"></span> sets × <span x-text="currentExercise?.reps"></span> reps
                                            </span>
                                            <span x-show="currentExercise?.duration_seconds">
                                                <span x-text="currentExercise?.duration_seconds"></span>s
                                            </span>
                                            <span x-show="currentExercise?.tempo" class="text-cyan-300">
                                                <span x-text="currentExercise?.tempo"></span>
                                            </span>
                                            <span x-show="currentExercise?.intensity" class="text-cyan-300">
                                                <span x-text="currentExercise?.intensity"></span> intensity
                                            </span>
                                        </div>
                                        <!-- Instructions Toggle -->
                                        <div x-show="currentExercise?.instructions" class="pt-2">
                                            <button
                                                @click="showInstructions = !showInstructions"
                                                class="inline-flex items-center gap-2 text-base md:text-lg text-cyan-300 hover:text-cyan-200 transition-colors"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span x-text="showInstructions ? 'Hide instructions' : 'Show instructions'"></span>
                                            </button>
                                            <div
                                                x-show="showInstructions"
                                                x-transition
                                                class="mt-3 p-5 app-panel rounded-lg text-left max-w-2xl mx-auto"
                                            >
                                                <p class="text-base md:text-lg text-white/70 whitespace-pre-line" x-text="currentExercise?.instructions"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mobile Next Exercise -->
                                    <div x-show="currentExerciseIndex < exercises.length - 1" class="md:hidden text-center text-white/50 shrink-0 text-lg">
                                        <span>Next: </span><span class="text-white/70" x-text="exercises[currentExerciseIndex + 1]?.name"></span>
                                    </div>

                                    <!-- Controls -->
                                    <div class="flex gap-4 justify-center flex-wrap shrink-0">
                                        <button x-show="state === 'ready'" @click="start"
                                            class="px-10 py-4 bg-emerald-500 text-white rounded-xl text-xl md:text-2xl font-semibold hover:bg-emerald-600 transition-colors go-cta">
                                            Start Practice
                                        </button>

                                        <button x-show="state === 'running'" @click="pause"
                                            class="px-10 py-4 bg-cyan-500 text-white rounded-xl text-xl md:text-2xl font-semibold hover:bg-cyan-600 transition-colors">
                                            Pause
                                        </button>

                                        <button x-show="state === 'paused'" @click="resume"
                                            class="px-10 py-4 bg-emerald-500 text-white rounded-xl text-xl md:text-2xl font-semibold hover:bg-emerald-600 transition-colors">
                                            Resume
                                        </button>

                                        <button x-show="state === 'running' || state === 'paused'" @click="next"
                                            class="px-10 py-4 bg-emerald-500 text-white rounded-xl text-xl md:text-2xl font-semibold hover:bg-emerald-600 transition-colors">
                                            Next
                                        </button>
                                    </div>

                                    <!-- Keyboard shortcuts hint (desktop only) -->
                                    <div x-show="state !== 'completed'" class="hidden md:flex gap-4 justify-center text-sm text-white/40 mt-2">
                                        <span x-show="state === 'ready'"><kbd class="px-1.5 py-0.5 bg-white/10 rounded text-xs">Enter</kbd> to start</span>
                                        <span x-show="state === 'running' || state === 'paused'"><kbd class="px-1.5 py-0.5 bg-white/10 rounded text-xs">Space</kbd> pause/resume</span>
                                        <span x-show="state === 'running' || state === 'paused'"><kbd class="px-1.5 py-0.5 bg-white/10 rounded text-xs">Enter</kbd> next</span>
                                    </div>
                                </div>

                                <!-- Next Exercise -->
                                <div class="md:col-span-2 hidden md:block md:pr-4">
                                    <div x-show="currentExerciseIndex < exercises.length - 1" class="space-y-4">
                                        <div class="text-2xl text-white/60 text-center mb-4">Next</div>
                                        <template x-for="(exercise, index) in exercises.slice(currentExerciseIndex + 1, currentExerciseIndex + 2)" :key="exercise.id">
                                            <div class="text-center p-5 app-card app-card--nested rounded-xl">
                                                <div class="text-2xl font-semibold text-white/80 line-clamp-3" x-text="exercise.name"></div>
                                            </div>
                                        </template>
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
