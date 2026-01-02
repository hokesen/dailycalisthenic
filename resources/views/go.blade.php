<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $template ? $template->name : __('Select a Template') }}
        </h2>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($template)
                        @if ($exercises->isEmpty())
                            <p class="text-gray-600">No exercises in this template yet.</p>
                        @else
                            <div
                                x-data="workoutTimer({ sessionId: {{ $session->id }}, exercises: @js($exercisesData) })"
                                class="max-w-2xl mx-auto">

                                <!-- Workout Complete Screen -->
                                <div x-show="state === 'completed'" class="text-center py-12">
                                    <div class="text-6xl mb-4">🎉</div>
                                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Workout Complete!</h2>
                                    <p class="text-gray-600 mb-6">Total time: <span x-text="formatTime(totalElapsedSeconds)"></span></p>

                                    <!-- Exercise Summary -->
                                    <div class="max-w-xl mx-auto mb-8">
                                        <div class="bg-gray-50 rounded-lg p-6 space-y-6">
                                            <!-- Completed Exercises -->
                                            <div x-show="completedExercises.length > 0">
                                                <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center justify-center gap-2">
                                                    <span class="text-green-600">✓</span>
                                                    <span>Completed (<span x-text="completedExercises.length"></span>)</span>
                                                </h3>
                                                <div class="space-y-2">
                                                    <template x-for="exercise in completedExercises" :key="exercise.id">
                                                        <div class="text-gray-700 bg-white rounded p-2 text-sm">
                                                            <span x-text="exercise.name"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- Skipped Exercises -->
                                            <div x-show="skippedExercises.length > 0">
                                                <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center justify-center gap-2">
                                                    <span class="text-yellow-600">⊘</span>
                                                    <span>Skipped (<span x-text="skippedExercises.length"></span>)</span>
                                                </h3>
                                                <div class="space-y-2">
                                                    <template x-for="exercise in skippedExercises" :key="exercise.id">
                                                        <div class="text-gray-700 bg-white rounded p-2 text-sm">
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
                                <div x-show="state !== 'completed'" class="space-y-8">
                                    <!-- Status and Progress -->
                                    <div class="text-center">
                                        <div class="mb-2">
                                            <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold"
                                                :class="isResting ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                                                x-text="isResting ? 'Rest Period' : 'Exercise'">
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500">Exercise <span x-text="currentExerciseIndex + 1"></span> of <span x-text="exercises.length"></span></p>
                                    </div>

                                    <!-- Circular Progress Timer -->
                                    <div class="flex justify-center">
                                        <div class="relative" style="width: 280px; height: 280px;">
                                            <svg class="transform -rotate-90" width="280" height="280">
                                                <!-- Background circle -->
                                                <circle cx="140" cy="140" r="120" stroke="#e5e7eb" stroke-width="20" fill="none"></circle>
                                                <!-- Progress circle -->
                                                <circle cx="140" cy="140" r="120"
                                                    :stroke="isResting ? '#3b82f6' : '#10b981'"
                                                    stroke-width="20"
                                                    fill="none"
                                                    stroke-linecap="round"
                                                    :stroke-dasharray="2 * Math.PI * 120"
                                                    :stroke-dashoffset="2 * Math.PI * 120 * (1 - progress)"
                                                    style="transition: stroke-dashoffset 0.1s linear;">
                                                </circle>
                                            </svg>
                                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                <div class="text-6xl font-bold text-gray-900" x-text="formatTime(timeRemaining)"></div>
                                                <div class="text-sm text-gray-500 mt-2" x-text="isResting ? 'Rest' : 'Go'"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Exercise Info -->
                                    <div class="text-center space-y-2">
                                        <h3 class="text-5xl font-bold text-gray-900" x-text="currentExercise?.name"></h3>
                                        <div x-show="currentExercise?.description" class="text-gray-600" x-text="currentExercise?.description"></div>
                                        <div class="flex gap-4 justify-center text-sm text-gray-500">
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
                                            class="px-8 py-4 bg-green-600 text-white rounded-lg font-semibold text-lg hover:bg-green-700 transition-colors">
                                            Start Workout
                                        </button>

                                        <button x-show="state === 'running'" @click="pause"
                                            class="px-8 py-4 bg-yellow-600 text-white rounded-lg font-semibold text-lg hover:bg-yellow-700 transition-colors">
                                            Pause
                                        </button>

                                        <button x-show="state === 'paused'" @click="resume"
                                            class="px-8 py-4 bg-green-600 text-white rounded-lg font-semibold text-lg hover:bg-green-700 transition-colors">
                                            Resume
                                        </button>

                                        <button x-show="state === 'running' || state === 'paused'" @click="skipToNext"
                                            class="px-8 py-4 bg-blue-600 text-white rounded-lg font-semibold text-lg hover:bg-blue-700 transition-colors">
                                            Skip
                                        </button>

                                        <button x-show="state === 'running' || state === 'paused'" @click="markCompleted"
                                            class="px-8 py-4 bg-purple-600 text-white rounded-lg font-semibold text-lg hover:bg-purple-700 transition-colors">
                                            Mark Completed
                                        </button>
                                    </div>

                                    <!-- Exercise List -->
                                    <div class="mt-8 space-y-2">
                                        <h4 class="text-lg font-semibold text-gray-700 mb-4">Exercises</h4>
                                        <template x-for="(exercise, index) in exercises" :key="exercise.id">
                                            <div class="border rounded-lg p-3"
                                                :class="index === currentExerciseIndex ? 'border-green-500 bg-green-50' : index < currentExerciseIndex ? 'border-gray-200 bg-gray-50 opacity-60' : 'border-gray-200'">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-gray-500 font-semibold" x-text="exercise.order + '.'"></span>
                                                        <div>
                                                            <div class="font-semibold text-gray-900" x-text="exercise.name"></div>
                                                            <div class="text-sm text-gray-600">
                                                                <span x-show="exercise.sets && exercise.reps">
                                                                    <span x-text="exercise.sets"></span> × <span x-text="exercise.reps"></span>
                                                                </span>
                                                                <span x-show="exercise.duration_seconds">
                                                                    <span x-text="exercise.duration_seconds"></span>s
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span x-show="index < currentExerciseIndex" class="text-green-600 font-semibold">✓</span>
                                                        <span x-show="index === currentExerciseIndex && state === 'running'" class="text-green-600 font-semibold">▶</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        @if ($templates->isEmpty())
                            <p class="text-gray-600">No workout templates available yet.</p>
                        @else
                            <div class="space-y-4">
                                <h4 class="text-lg font-semibold text-gray-700">Available Templates</h4>
                                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($templates as $availableTemplate)
                                        <div class="border border-gray-200 rounded-lg p-4 flex flex-col">
                                            <h5 class="font-semibold text-gray-900 mb-2">{{ $availableTemplate->name }}</h5>
                                            @if ($availableTemplate->description)
                                                <p class="text-sm text-gray-600 mb-2">{{ $availableTemplate->description }}</p>
                                            @endif

                                            @if ($availableTemplate->exercises->isNotEmpty())
                                                <div class="mb-4 flex-grow">
                                                    <p class="text-base font-bold text-gray-800 mb-3">Exercises:</p>
                                                    <div class="space-y-2.5">
                                                        @foreach ($availableTemplate->exercises as $exercise)
                                                            <div class="border border-gray-300 rounded-md p-3 bg-gray-50">
                                                                <div class="flex items-start">
                                                                    <span class="text-gray-500 mr-2.5 font-semibold text-base">{{ $exercise->pivot->order }}.</span>
                                                                    <div class="flex-grow">
                                                                        <div class="font-semibold text-gray-900 text-base">{{ $exercise->name }}</div>
                                                                        <div class="text-gray-600 mt-1 text-sm space-x-2.5">
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
                                                    <p class="text-base font-medium text-gray-600 mb-4">~{{ $availableDuration }} minutes</p>
                                                @endif
                                            @else
                                                <p class="text-base text-gray-500 mb-4 flex-grow">No exercises yet</p>
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
