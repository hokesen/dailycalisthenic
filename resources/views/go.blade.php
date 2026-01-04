<x-app-layout>
    <div class="py-12 bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-100">
                    @if ($template)
                        @if ($exercises->isEmpty())
                            <p class="text-gray-400">No exercises in this template yet.</p>
                        @else
                            <div
                                x-data="workoutTimer({ sessionId: {{ $session->id }}, exercises: @js($exercisesData) })"
                                x-effect="$dispatch('workout-state-changed', { running: state === 'running' })"
                                class="w-full">

                                <!-- Workout Complete Screen -->
                                <div x-show="state === 'completed'" class="text-center py-12">
                                    <div class="text-6xl mb-4">🎉</div>
                                    <h2 class="text-3xl font-bold text-gray-100 mb-2">Workout Complete!</h2>
                                    <p class="text-gray-300 mb-6">Total time: <span x-text="formatTime(totalElapsedSeconds)"></span></p>

                                    <!-- Exercise Summary -->
                                    <div class="max-w-xl mx-auto mb-8">
                                        <div class="bg-gray-700 rounded-lg p-6 space-y-6">
                                            <!-- Completed Exercises -->
                                            <div x-show="completedExercises.length > 0">
                                                <h3 class="text-lg font-semibold text-gray-100 mb-3 flex items-center justify-center gap-2">
                                                    <span class="text-green-400">✓</span>
                                                    <span>Completed (<span x-text="completedExercises.length"></span>)</span>
                                                </h3>
                                                <div class="space-y-2">
                                                    <template x-for="exercise in completedExercises" :key="exercise.id">
                                                        <div class="text-gray-200 bg-gray-600 rounded p-2 text-sm">
                                                            <span x-text="exercise.name"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- Skipped Exercises -->
                                            <div x-show="skippedExercises.length > 0">
                                                <h3 class="text-lg font-semibold text-gray-100 mb-3 flex items-center justify-center gap-2">
                                                    <span class="text-yellow-400">⊘</span>
                                                    <span>Skipped (<span x-text="skippedExercises.length"></span>)</span>
                                                </h3>
                                                <div class="space-y-2">
                                                    <template x-for="exercise in skippedExercises" :key="exercise.id">
                                                        <div class="text-gray-200 bg-gray-600 rounded p-2 text-sm">
                                                            <span x-text="exercise.name"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-4 justify-center">
                                        <a href="{{ route('dashboard') }}">
                                            <x-primary-button type="button">
                                                Back to Dashboard
                                            </x-primary-button>
                                        </a>
                                        <a href="{{ route('go.index', ['template' => $template->id]) }}">
                                            <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                Do it again
                                            </button>
                                        </a>
                                    </div>
                                </div>

                                <!-- Main Timer Screen -->
                                <div x-show="state !== 'completed'" class="flex flex-col md:grid md:grid-cols-12 gap-6">
                                    <!-- Previous Exercises -->
                                    <div class="md:col-span-2 order-1 md:order-none">
                                        <div x-show="currentExerciseIndex > 0" class="space-y-2 md:space-y-4">
                                            <div class="text-lg md:text-2xl text-gray-500 dark:text-gray-400 text-center mb-2 md:mb-4">Previous</div>
                                            <div class="flex md:flex-col gap-2 overflow-x-auto md:overflow-x-visible md:space-y-2 max-h-none md:max-h-[70vh] md:overflow-y-auto pb-2 md:pb-0">
                                                <template x-for="(exercise, index) in exercises.slice(0, currentExerciseIndex)" :key="exercise.id">
                                                    <div class="flex-shrink-0 w-24 md:w-auto text-center p-2 bg-gray-700 rounded border border-gray-600 opacity-60">
                                                        <div class="text-xs text-gray-500 mb-1" x-text="exercise.order"></div>
                                                        <div class="text-xs md:text-sm font-semibold text-gray-300 line-clamp-2" x-text="exercise.name"></div>
                                                        <div class="text-green-400 text-xs mt-1">✓</div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Main Content -->
                                    <div class="md:col-span-8 flex flex-col justify-center space-y-6 order-2 md:order-none">
                                        <!-- Status and Progress -->
                                        <div class="text-center">
                                            <div class="mb-2">
                                                <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold"
                                                    :class="isResting ? 'bg-blue-900 text-blue-200' : 'bg-green-900 text-green-200'"
                                                    x-text="isResting ? 'Rest Period' : 'Exercise'">
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-400">Exercise <span x-text="currentExerciseIndex + 1"></span> of <span x-text="exercises.length"></span></p>
                                        </div>

                                        <!-- Circular Progress Timer -->
                                        <div class="flex justify-center">
                                            <div class="relative" style="width: 240px; height: 240px;">
                                                <svg class="transform -rotate-90" width="240" height="240">
                                                    <!-- Background circle -->
                                                    <circle cx="120" cy="120" r="100" stroke="#374151" stroke-width="16" fill="none"></circle>
                                                    <!-- Progress circle -->
                                                    <circle cx="120" cy="120" r="100"
                                                        :stroke="isResting ? '#60a5fa' : '#34d399'"
                                                        stroke-width="16"
                                                        fill="none"
                                                        stroke-linecap="round"
                                                        :stroke-dasharray="2 * Math.PI * 100"
                                                        :stroke-dashoffset="2 * Math.PI * 100 * (1 - progress)"
                                                        style="transition: stroke-dashoffset 0.1s linear;">
                                                    </circle>
                                                </svg>
                                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                    <div class="text-5xl font-bold text-gray-100" x-text="formatTime(timeRemaining)"></div>
                                                    <div class="text-sm text-gray-400 mt-2" x-text="isResting ? 'Rest' : 'Go'"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Exercise Info -->
                                        <div class="text-center space-y-2">
                                            <h3 class="text-4xl font-bold text-gray-100" x-text="currentExercise?.name"></h3>
                                            <div x-show="currentExercise?.description" class="text-gray-300" x-text="currentExercise?.description"></div>
                                            <div class="flex gap-4 justify-center text-sm text-gray-400">
                                                <span x-show="currentExercise?.sets && currentExercise?.reps">
                                                    <span x-text="currentExercise?.sets"></span> sets × <span x-text="currentExercise?.reps"></span> reps
                                                </span>
                                                <span x-show="currentExercise?.duration_seconds">
                                                    <span x-text="currentExercise?.duration_seconds"></span>s
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Controls -->
                                        <div class="flex gap-4 justify-center">
                                            <button x-show="state === 'ready'" @click="start"
                                                class="px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors">
                                                Start Workout
                                            </button>

                                            <button x-show="state === 'running'" @click="pause"
                                                class="px-6 py-3 bg-yellow-600 text-white rounded-lg font-semibold hover:bg-yellow-700 transition-colors">
                                                Pause
                                            </button>

                                            <button x-show="state === 'paused'" @click="resume"
                                                class="px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors">
                                                Resume
                                            </button>

                                            <button x-show="state === 'running' || state === 'paused'" @click="skipToNext"
                                                class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                                                Skip
                                            </button>

                                            <button x-show="state === 'running' || state === 'paused'" @click="markCompleted"
                                                class="px-6 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                                                Mark Completed
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Next Exercises -->
                                    <div class="md:col-span-2 order-3 md:order-none">
                                        <div x-show="currentExerciseIndex < exercises.length - 1" class="space-y-2 md:space-y-4">
                                            <div class="text-lg md:text-2xl text-gray-500 dark:text-gray-400 text-center mb-2 md:mb-4">Next</div>
                                            <div class="flex md:flex-col gap-2 overflow-x-auto md:overflow-x-visible md:space-y-2 max-h-none md:max-h-[70vh] md:overflow-y-auto pb-2 md:pb-0">
                                                <template x-for="(exercise, index) in exercises.slice(currentExerciseIndex + 1)" :key="exercise.id">
                                                    <div class="flex-shrink-0 w-24 md:w-auto text-center p-2 bg-gray-700 rounded border border-gray-600">
                                                        <div class="text-xs text-gray-500 mb-1" x-text="exercise.order"></div>
                                                        <div class="text-xs md:text-sm font-semibold text-gray-300 line-clamp-2" x-text="exercise.name"></div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        @if ($templates->isEmpty())
                            <p class="text-gray-400">No workout templates available yet.</p>
                        @else
                            <div class="space-y-4">
                                <h4 class="text-lg font-semibold text-gray-300">Available Templates</h4>
                                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($templates as $availableTemplate)
                                        <div class="border border-gray-600 rounded-lg p-4 flex flex-col bg-gray-700">
                                            <h5 class="font-semibold text-gray-100 mb-2">{{ $availableTemplate->name }}</h5>
                                            @if ($availableTemplate->description)
                                                <p class="text-sm text-gray-300 mb-2">{{ $availableTemplate->description }}</p>
                                            @endif

                                            @if ($availableTemplate->exercises->isNotEmpty())
                                                <div class="mb-4 flex-grow">
                                                    <p class="text-base font-bold text-gray-200 mb-3">Exercises:</p>
                                                    <div class="space-y-2.5">
                                                        @foreach ($availableTemplate->exercises as $exercise)
                                                            <div class="border border-gray-500 rounded-md p-3 bg-gray-600">
                                                                <div class="flex items-start">
                                                                    <span class="text-gray-400 mr-2.5 font-semibold text-base">{{ $exercise->pivot->order }}.</span>
                                                                    <div class="flex-grow">
                                                                        <div class="font-semibold text-gray-100 text-base">{{ $exercise->name }}</div>
                                                                        <div class="text-gray-300 mt-1 text-sm space-x-2.5">
                                                                            @if ($exercise->pivot->sets && $exercise->pivot->reps)
                                                                                <span class="font-medium">{{ $exercise->pivot->sets }} × {{ $exercise->pivot->reps }}</span>
                                                                            @endif
                                                                            @if ($exercise->pivot->duration_seconds)
                                                                                <span class="font-medium">{{ $exercise->pivot->duration_seconds }}s</span>
                                                                            @endif
                                                                            @if ($exercise->pivot->rest_after_seconds)
                                                                                <span>• Rest: {{ $exercise->pivot->rest_after_seconds }}s</span>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                @php
                                                    $availableDuration = $availableTemplate->calculateDurationMinutes();
                                                @endphp
                                                @if ($availableDuration > 0)
                                                    <p class="text-base font-medium text-gray-300 mb-4">~{{ $availableDuration }} minutes</p>
                                                @endif
                                            @else
                                                <p class="text-base text-gray-400 mb-4 flex-grow">No exercises yet</p>
                                            @endif

                                            <div>
                                                <a href="{{ route('go.index', ['template' => $availableTemplate->id]) }}">
                                                    <x-primary-button type="button" class="w-full justify-center">
                                                        Go
                                                    </x-primary-button>
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
