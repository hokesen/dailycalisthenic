import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

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
    exerciseCompletionStatus: [],

    get currentExercise() {
        return this.exercises[this.currentExerciseIndex] || {};
    },

    get progress() {
        const total = this.isResting ? this.currentExercise.rest_after_seconds : this.currentExercise.duration_seconds;
        if (total === 0) {
            return 1;
        }
        return 1 - (this.timeRemaining / total);
    },

    get completedExercises() {
        return this.exercises.filter((_, index) => this.exerciseCompletionStatus[index] === 'completed');
    },

    get skippedExercises() {
        return this.exercises.filter((_, index) =>
            this.exerciseCompletionStatus[index] === 'skipped' ||
            this.exerciseCompletionStatus[index] === 'marked_completed'
        );
    },

    init() {
        this.timeRemaining = this.currentExercise.duration_seconds;
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
    },

    pause() {
        this.state = 'paused';
        this.stopTimer();
    },

    resume() {
        this.state = 'running';
        this.startTimer();
    },

    startTimer() {
        this.intervalId = setInterval(() => {
            if (this.timeRemaining > 0) {
                this.timeRemaining--;
                this.totalElapsedSeconds++;
            } else {
                this.handleTimerComplete();
            }
        }, 1000);
    },

    stopTimer() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    },

    handleTimerComplete() {
        if (!this.isResting) {
            this.exerciseCompletionStatus[this.currentExerciseIndex] = 'completed';
            this.updateExerciseCompletion(this.currentExerciseIndex, 'completed');
            if (this.currentExercise.rest_after_seconds > 0 && this.currentExerciseIndex < this.exercises.length - 1) {
                this.isResting = true;
                this.timeRemaining = this.currentExercise.rest_after_seconds;
            } else {
                this.moveToNextExercise();
            }
        } else {
            this.moveToNextExercise();
        }
    },

    moveToNextExercise() {
        this.isResting = false;
        this.currentExerciseIndex++;

        if (this.currentExerciseIndex >= this.exercises.length) {
            this.completeWorkout();
        } else {
            this.timeRemaining = this.currentExercise.duration_seconds;
        }
    },

    skipToNext() {
        if (!this.isResting && !this.exerciseCompletionStatus[this.currentExerciseIndex]) {
            this.exerciseCompletionStatus[this.currentExerciseIndex] = 'skipped';
            this.updateExerciseCompletion(this.currentExerciseIndex, 'skipped');
        }
        this.handleTimerComplete();
    },

    markCompleted() {
        this.exerciseCompletionStatus[this.currentExerciseIndex] = 'marked_completed';
        this.updateExerciseCompletion(this.currentExerciseIndex, 'marked_completed');
        this.isResting = false;
        this.moveToNextExercise();
    },

    updateExerciseCompletion(exerciseIndex, status) {
        const exercise = this.exercises[exerciseIndex];
        if (!exercise) {
            return;
        }

        fetch(`/go/${this.sessionId}/update`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                status: this.state === 'completed' ? 'completed' : 'in_progress',
                total_duration_seconds: this.totalElapsedSeconds,
                exercise_completion: [{
                    exercise_id: exercise.id,
                    order: exercise.order,
                    status: status
                }]
            })
        });
    },

    completeWorkout() {
        this.stopTimer();
        this.state = 'completed';
        this.updateSessionStatus('completed');
    },

    updateSessionStatus(status) {
        fetch(`/go/${this.sessionId}/update`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                status: status,
                total_duration_seconds: this.totalElapsedSeconds
            })
        });
    }
}));

Alpine.start();
