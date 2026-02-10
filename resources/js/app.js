import './bootstrap';

import Alpine from 'alpinejs';
import { csrfFetch, submitForm, deleteResource, updateResource } from './utils/fetchHelper';
import ganttChart from './components/ganttChart';

window.Alpine = Alpine;

// Make fetch helpers available globally
window.csrfFetch = csrfFetch;
window.submitForm = submitForm;
window.deleteResource = deleteResource;
window.updateResource = updateResource;

// Register ganttChart component
Alpine.data('ganttChart', ganttChart);

// Register workoutTimer component
Alpine.data('workoutTimer', (config) => ({
    sessionId: config.sessionId || 0,
    exercises: config.exercises || [],
    currentExerciseIndex: 0,
    state: 'ready',
    isResting: false,
    timeRemaining: 0,
    totalElapsedSeconds: 0,
    intervalId: null,
    timerHandle: null,
    lastFrameTime: null,
    currentSegmentMs: 0,
    remainingMs: 0,
    totalElapsedMs: 0,
    exerciseCompletionStatus: [],
    autosaveIntervalId: null,
    autosaveIntervalMs: 15000,

    get currentExercise() {
        return this.exercises[this.currentExerciseIndex] || {};
    },

    get progress() {
        if (this.currentSegmentMs === 0) {
            return 1;
        }
        return 1 - (this.remainingMs / this.currentSegmentMs);
    },

    get completedExercises() {
        return this.exercises.filter((_, index) =>
            this.exerciseCompletionStatus[index] === 'completed'
        );
    },

    init() {
        this.setSegment(this.currentExercise.duration_seconds);
        this.setupAutosaveListeners();

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Don't trigger shortcuts when typing in inputs
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }

            // Space: pause/resume
            if (e.code === 'Space' && this.state !== 'completed' && this.state !== 'ready') {
                e.preventDefault();
                if (this.state === 'running') {
                    this.pause();
                } else if (this.state === 'paused') {
                    this.resume();
                }
            }

            // Enter: start (when ready) or next (when running/paused)
            if (e.code === 'Enter') {
                e.preventDefault();
                if (this.state === 'ready') {
                    this.start();
                } else if (this.state === 'running' || this.state === 'paused') {
                    this.next();
                }
            }
        });
    },

    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return mins + ':' + (secs < 10 ? '0' : '') + secs;
    },

    start() {
        this.state = 'running';
        this.startTimer();
        this.updateSessionStatus('in_progress');
        this.startAutosave();
    },

    pause() {
        this.state = 'paused';
        this.stopTimer();
        this.updateSessionStatus('in_progress');
    },

    resume() {
        this.state = 'running';
        this.startTimer();
        this.startAutosave();
    },

    startTimer() {
        if (!this.tick) {
            this.tick = (now) => {
                if (this.state !== 'running') {
                    return;
                }

                if (!this.lastFrameTime) {
                    this.lastFrameTime = now;
                }

                const delta = now - this.lastFrameTime;
                this.lastFrameTime = now;

                this.remainingMs = Math.max(0, this.remainingMs - delta);
                this.totalElapsedMs += delta;

                this.timeRemaining = Math.max(0, Math.ceil(this.remainingMs / 1000));
                this.totalElapsedSeconds = Math.floor(this.totalElapsedMs / 1000);

                if (this.remainingMs <= 0) {
                    this.remainingMs = 0;
                    this.timeRemaining = 0;
                    requestAnimationFrame(() => this.handleTimerComplete());
                    return;
                }

                this.timerHandle = requestAnimationFrame(this.tick);
            };
        }

        this.lastFrameTime = performance.now();
        this.timerHandle = requestAnimationFrame(this.tick);
    },

    stopTimer() {
        if (this.timerHandle) {
            cancelAnimationFrame(this.timerHandle);
            this.timerHandle = null;
        }
        this.lastFrameTime = null;
    },

    handleTimerComplete() {
        if (!this.isResting) {
            this.exerciseCompletionStatus[this.currentExerciseIndex] = 'completed';
            this.updateExerciseCompletion(this.currentExerciseIndex, 'completed');
            if (this.currentExercise.rest_after_seconds > 0 && this.currentExerciseIndex < this.exercises.length - 1) {
                this.isResting = true;
                this.setSegment(this.currentExercise.rest_after_seconds);
            } else {
                this.moveToNextExercise();
            }
        } else {
            this.moveToNextExercise();
        }

        if (this.state === 'running') {
            this.lastFrameTime = performance.now();
            this.timerHandle = requestAnimationFrame(this.tick);
        }
    },

    moveToNextExercise() {
        this.isResting = false;
        this.currentExerciseIndex++;

        if (this.currentExerciseIndex >= this.exercises.length) {
            this.completeWorkout();
        } else {
            this.setSegment(this.currentExercise.duration_seconds);
        }
    },

    next() {
        if (!this.isResting && !this.exerciseCompletionStatus[this.currentExerciseIndex]) {
            this.exerciseCompletionStatus[this.currentExerciseIndex] = 'completed';
            // Calculate actual time spent on this exercise
            const actualDuration = this.currentExercise.duration_seconds - this.timeRemaining;
            this.updateExerciseCompletion(this.currentExerciseIndex, 'completed', actualDuration);
        }
        this.isResting = false;
        this.moveToNextExercise();
    },

    updateExerciseCompletion(exerciseIndex, status, actualDuration = null) {
        const exercise = this.exercises[exerciseIndex];
        if (!exercise) {
            return;
        }

        const exerciseData = {
            exercise_id: exercise.id,
            order: exercise.order,
            status: status
        };

        // Include actual duration for skipped exercises
        if (actualDuration !== null) {
            exerciseData.duration_seconds = actualDuration;
        }

        csrfFetch(`/go/${this.sessionId}/update`, {
            method: 'PATCH',
            body: JSON.stringify({
                status: this.state === 'completed' ? 'completed' : 'in_progress',
                total_duration_seconds: this.totalElapsedSeconds,
                exercise_completion: [exerciseData]
            })
        });
    },

    completeWorkout() {
        this.stopTimer();
        this.state = 'completed';
        this.updateSessionStatus('completed');
        this.stopAutosave();
    },

    setSegment(seconds) {
        const duration = Number(seconds || 0);
        this.currentSegmentMs = duration * 1000;
        this.remainingMs = this.currentSegmentMs;
        this.timeRemaining = duration;
    },

    startAutosave() {
        if (this.autosaveIntervalId) {
            return;
        }

        this.autosaveIntervalId = setInterval(() => {
            if (this.state === 'running' || this.state === 'paused') {
                this.flushAutosave();
            }
        }, this.autosaveIntervalMs);
    },

    stopAutosave() {
        if (this.autosaveIntervalId) {
            clearInterval(this.autosaveIntervalId);
            this.autosaveIntervalId = null;
        }
    },

    setupAutosaveListeners() {
        const flush = () => {
            if (this.state === 'running' || this.state === 'paused') {
                this.flushAutosave(true);
            }
        };

        window.addEventListener('pagehide', flush);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                flush();
            }
        });
    },

    flushAutosave(useKeepalive = false) {
        this.updateSessionStatus('in_progress', { keepalive: useKeepalive });
    },

    updateSessionStatus(status, options = {}) {
        csrfFetch(`/go/${this.sessionId}/update`, {
            method: 'PATCH',
            keepalive: options.keepalive === true,
            body: JSON.stringify({
                status: status,
                total_duration_seconds: this.totalElapsedSeconds
            })
        });
    }
}));

Alpine.start();
