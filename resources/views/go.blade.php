<x-app-layout>
    <div class="bg-gray-900 h-full overflow-hidden flex flex-col">
        <div class="w-full flex-1 flex items-center justify-center min-h-0">
            <div class="w-full h-full flex items-center justify-center">
                <div class="w-full h-full text-gray-100">
                    @if ($exercises->isEmpty())
                        <p class="text-gray-400">No exercises in this template yet.</p>
                    @else
                        <div
                            x-data="workoutTimer({ sessionId: {{ $session->id }}, exercises: @js($exercisesData) })"
                            x-effect="$dispatch('workout-state-changed', { running: state === 'running' })"
                            class="w-full h-full">

                            <!-- Workout Complete Screen -->
                            <div x-show="state === 'completed'" class="text-center py-4 flex flex-col items-center justify-between h-full overflow-hidden">
                                <div class="shrink-0">
                                    <div class="text-6xl mb-4">🎉</div>
                                    <h2 class="text-4xl md:text-5xl font-bold text-gray-100 mb-2">Workout Complete!</h2>
                                    <p class="text-2xl md:text-3xl text-gray-300">Total time: <span x-text="formatTime(totalElapsedSeconds)"></span></p>
                                </div>

                                <!-- Exercise Summary -->
                                <div class="max-w-4xl mx-auto flex-1 min-h-0 overflow-auto my-4 w-full px-4">
                                    <div class="bg-gray-700 rounded-lg p-4 space-y-4">
                                        <!-- Completed Exercises -->
                                        <div x-show="completedExercises.length > 0">
                                            <h3 class="text-xl font-semibold text-gray-100 mb-3 flex items-center justify-center gap-2">
                                                <span class="text-green-400">✓</span>
                                                <span>Completed (<span x-text="completedExercises.length"></span>)</span>
                                            </h3>
                                            <div class="space-y-2">
                                                <template x-for="exercise in completedExercises" :key="exercise.id">
                                                    <div class="text-gray-200 bg-gray-600 rounded p-2 text-base">
                                                        <span x-text="exercise.name"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Skipped Exercises -->
                                        <div x-show="skippedExercises.length > 0">
                                            <h3 class="text-xl font-semibold text-gray-100 mb-3 flex items-center justify-center gap-2">
                                                <span class="text-yellow-400">⊘</span>
                                                <span>Skipped (<span x-text="skippedExercises.length"></span>)</span>
                                            </h3>
                                            <div class="space-y-2">
                                                <template x-for="exercise in skippedExercises" :key="exercise.id">
                                                    <div class="text-gray-200 bg-gray-600 rounded p-2 text-base">
                                                        <span x-text="exercise.name"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-4 justify-center flex-wrap shrink-0">
                                    <a href="{{ route('dashboard') }}">
                                        <button type="button" class="px-8 py-4 bg-indigo-600 text-white rounded-xl text-xl font-semibold hover:bg-indigo-700 transition-colors">
                                            Back to Dashboard
                                        </button>
                                    </a>
                                    <a href="{{ route('go.index', ['template' => $template->id]) }}">
                                        <button type="button" class="px-8 py-4 bg-gray-800 text-white rounded-xl text-xl font-semibold hover:bg-gray-700 transition-colors">
                                            Do it again
                                        </button>
                                    </a>
                                </div>
                            </div>

                            <!-- Main Timer Screen -->
                            <div x-show="state !== 'completed'" class="h-full w-full px-8 py-4 grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                <!-- Main Content -->
                                <div class="md:col-span-10 flex flex-col justify-between items-center h-full w-full py-2">
                                    <!-- Status and Progress -->
                                    <div class="text-center shrink-0">
                                        <div>
                                            <span class="inline-block px-6 py-2 rounded-full text-xl font-semibold"
                                                :class="isResting ? 'bg-blue-900 text-blue-200' : 'bg-green-900 text-green-200'">
                                                <span x-text="isResting ? 'Rest Period' : 'Exercise'"></span>
                                                <span x-text="' ' + (currentExerciseIndex + 1) + ' of ' + exercises.length"></span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Circular Progress Timer -->
                                    <div class="flex justify-center items-center flex-1 min-h-0">
                                        <div class="relative w-full max-w-[min(50vh,400px)] aspect-square">
                                            <svg class="transform -rotate-90 w-full h-full" viewBox="0 0 480 480">
                                                <!-- Background circle -->
                                                <circle cx="240" cy="240" r="200" stroke="#374151" stroke-width="32" fill="none"></circle>
                                                <!-- Progress circle -->
                                                <circle cx="240" cy="240" r="200"
                                                    :stroke="isResting ? '#60a5fa' : '#34d399'"
                                                    stroke-width="32"
                                                    fill="none"
                                                    stroke-linecap="round"
                                                    :stroke-dasharray="2 * Math.PI * 200"
                                                    :stroke-dashoffset="2 * Math.PI * 200 * (1 - progress)"
                                                    style="transition: stroke-dashoffset 0.1s linear;">
                                                </circle>
                                            </svg>
                                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                <div class="text-7xl md:text-8xl font-bold text-gray-100" x-text="formatTime(timeRemaining)"></div>
                                                <div class="text-2xl md:text-3xl text-gray-400 mt-2" x-text="isResting ? 'Rest' : 'Go'"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Exercise Info -->
                                    <div class="text-center space-y-2 shrink-0">
                                        <h3 class="text-4xl md:text-5xl font-bold text-gray-100" x-text="currentExercise?.name"></h3>
                                        <div x-show="currentExercise?.description" class="text-xl text-gray-300" x-text="currentExercise?.description"></div>
                                        <div class="flex gap-4 justify-center text-lg text-gray-400">
                                            <span x-show="currentExercise?.sets && currentExercise?.reps">
                                                <span x-text="currentExercise?.sets"></span> sets × <span x-text="currentExercise?.reps"></span> reps
                                            </span>
                                            <span x-show="currentExercise?.duration_seconds">
                                                <span x-text="currentExercise?.duration_seconds"></span>s
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Mobile Next Exercise -->
                                    <div x-show="currentExerciseIndex < exercises.length - 1" class="md:hidden text-center text-gray-400 shrink-0 text-lg">
                                        <span>Next: </span><span class="text-gray-300" x-text="exercises[currentExerciseIndex + 1]?.name"></span>
                                    </div>

                                    <!-- Controls -->
                                    <div class="flex gap-4 justify-center flex-wrap shrink-0">
                                        <button x-show="state === 'ready'" @click="start"
                                            class="px-8 py-4 bg-green-600 text-white rounded-xl text-xl font-semibold hover:bg-green-700 transition-colors">
                                            Start Workout
                                        </button>

                                        <button x-show="state === 'running'" @click="pause"
                                            class="px-8 py-4 bg-yellow-600 text-white rounded-xl text-xl font-semibold hover:bg-yellow-700 transition-colors">
                                            Pause
                                        </button>

                                        <button x-show="state === 'paused'" @click="resume"
                                            class="px-8 py-4 bg-green-600 text-white rounded-xl text-xl font-semibold hover:bg-green-700 transition-colors">
                                            Resume
                                        </button>

                                        <button x-show="state === 'running' || state === 'paused'" @click="skipToNext"
                                            class="px-8 py-4 bg-blue-600 text-white rounded-xl text-xl font-semibold hover:bg-blue-700 transition-colors">
                                            Skip
                                        </button>

                                        <button x-show="state === 'running' || state === 'paused'" @click="markCompleted"
                                            class="px-8 py-4 bg-purple-600 text-white rounded-xl text-xl font-semibold hover:bg-purple-700 transition-colors">
                                            Mark Completed
                                        </button>
                                    </div>
                                </div>

                                <!-- Next Exercise -->
                                <div class="md:col-span-2 hidden md:block">
                                    <div x-show="currentExerciseIndex < exercises.length - 1" class="space-y-4">
                                        <div class="text-2xl text-gray-400 text-center mb-4">Next</div>
                                        <template x-for="(exercise, index) in exercises.slice(currentExerciseIndex + 1, currentExerciseIndex + 2)" :key="exercise.id">
                                            <div class="text-center p-4 bg-gray-700 rounded border border-gray-600">
                                                <div class="text-lg text-gray-500 mb-2" x-text="exercise.order"></div>
                                                <div class="text-xl font-semibold text-gray-300 line-clamp-3" x-text="exercise.name"></div>
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
