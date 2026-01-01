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
        this.handleTimerComplete();
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
