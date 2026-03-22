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
window.getCurrentCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
window.refreshAppCsrfToken = async () => {
    const refreshUrl = document.querySelector('meta[name="app-csrf-refresh"]')?.content;

    if (!refreshUrl) {
        return window.getCurrentCsrfToken();
    }

    const response = await fetch(refreshUrl, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok || response.redirected) {
        return null;
    }

    const payload = await response.json().catch(() => null);
    const token = payload?.token;

    if (!token) {
        return null;
    }

    document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
    document.querySelectorAll('input[name="_token"]').forEach((input) => {
        input.value = token;
    });

    return token;
};

const refreshTokenSilently = () => {
    window.refreshAppCsrfToken().catch(() => null);
};

window.addEventListener('focus', refreshTokenSilently);
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        refreshTokenSilently();
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.matches('[data-refresh-csrf]')) {
        return;
    }

    if (form.dataset.csrfRefreshing === 'true') {
        return;
    }

    event.preventDefault();
    form.dataset.csrfRefreshing = 'true';

    const token = await window.refreshAppCsrfToken().catch(() => null);

    if (!token) {
        form.dataset.csrfRefreshing = 'false';
        window.location.reload();

        return;
    }

    form.submit();
});

// Register ganttChart component
Alpine.data('ganttChart', ganttChart);

// Register workoutTimer component
Alpine.data('workoutTimer', (config) => ({
    sessionId: config.sessionId || 0,
    items: config.items || config.exercises || [],
    currentItemIndex: 0,
    currentRepeat: 1,
    state: 'ready',
    phase: 'work',
    restTarget: 'next_item',
    timeRemaining: 0,
    currentSegmentElapsedSeconds: 0,
    totalElapsedSeconds: 0,
    timerHandle: null,
    lastFrameTime: null,
    currentSegmentMs: 0,
    remainingMs: 0,
    currentSegmentElapsedMs: 0,
    currentBlockElapsedMs: 0,
    totalElapsedMs: 0,
    completedItemStates: [],
    autosaveIntervalId: null,
    autosaveIntervalMs: 15000,
    audioEnabled: false,
    audioContext: null,
    lastCountdownSecond: null,

    get currentItem() {
        return this.items[this.currentItemIndex] || null;
    },

    get isResting() {
        return this.phase === 'rest';
    },

    get isCurrentSegmentManual() {
        if (this.isResting || !this.currentItem) {
            return false;
        }

        return this.currentItem.completion_mode === 'manual' || !this.currentItem.duration_seconds;
    },

    get displaySeconds() {
        return this.isCurrentSegmentManual ? this.currentSegmentElapsedSeconds : this.timeRemaining;
    },

    get progress() {
        if (!this.currentItem) {
            return 1;
        }

        if (this.isResting) {
            if (this.currentSegmentMs === 0) {
                return 0;
            }

            return 1 - (this.remainingMs / this.currentSegmentMs);
        }

        const repeats = this.getRepeatCount(this.currentItem);
        const completedRepeats = Math.max(0, this.currentRepeat - 1);

        if (this.isCurrentSegmentManual || this.currentSegmentMs === 0) {
            return repeats === 0 ? 0 : completedRepeats / repeats;
        }

        const repeatProgress = 1 - (this.remainingMs / this.currentSegmentMs);

        return (completedRepeats + repeatProgress) / repeats;
    },

    get completedExercises() {
        return this.items.filter((item, index) =>
            this.completedItemStates[index] === 'completed' && item.track_completion !== false
        );
    },

    get nextSegment() {
        if (!this.currentItem) {
            return null;
        }

        if (this.isResting) {
            if (this.restTarget === 'next_repeat') {
                return {
                    label: `${this.currentItem.name} · Rep ${this.currentRepeat + 1} of ${this.getRepeatCount(this.currentItem)}`,
                    detail: this.currentItem.distance_label || this.currentItem.target_cue || this.currentItem.linked_name || null,
                };
            }

            const nextItem = this.items[this.currentItemIndex + 1] || null;
            if (!nextItem) {
                return null;
            }

            return {
                label: nextItem.name,
                detail: nextItem.linked_name || nextItem.distance_label || nextItem.target_cue || null,
            };
        }

        if (this.currentRepeat < this.getRepeatCount(this.currentItem)) {
            return {
                label: `Rest`,
                detail: this.currentItem.rest_after_seconds ? this.formatTime(this.currentItem.rest_after_seconds) : 'Transition',
            };
        }

        if ((this.currentItem.rest_after_seconds || 0) > 0 && this.currentItemIndex < this.items.length - 1) {
            return {
                label: `Rest`,
                detail: this.formatTime(this.currentItem.rest_after_seconds),
            };
        }

        const nextItem = this.items[this.currentItemIndex + 1] || null;

        return nextItem ? {
            label: nextItem.name,
            detail: nextItem.linked_name || nextItem.distance_label || nextItem.target_cue || null,
        } : null;
    },

    get nextButtonLabel() {
        if (this.isResting) {
            return 'Skip Rest';
        }

        return this.isCurrentSegmentManual ? 'Complete Block' : 'Next';
    },

    get timerCaption() {
        if (this.isResting) {
            return 'Rest';
        }

        return this.isCurrentSegmentManual ? 'Count Up' : 'Remaining';
    },

    init() {
        if (!this.items.length) {
            return;
        }

        this.setWorkSegment(true);
        this.setupAutosaveListeners();

        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }

            if (e.code === 'Space' && this.state !== 'completed' && this.state !== 'ready') {
                e.preventDefault();
                if (this.state === 'running') {
                    this.pause();
                } else if (this.state === 'paused') {
                    this.resume();
                }
            }

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
        if (!this.items.length) {
            return;
        }

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

                this.totalElapsedMs += delta;
                this.totalElapsedSeconds = Math.floor(this.totalElapsedMs / 1000);

                if (this.isCurrentSegmentManual && !this.isResting) {
                    this.currentSegmentElapsedMs += delta;
                    this.currentBlockElapsedMs += delta;
                    this.currentSegmentElapsedSeconds = Math.floor(this.currentSegmentElapsedMs / 1000);
                } else {
                    this.remainingMs = Math.max(0, this.remainingMs - delta);
                    this.currentSegmentElapsedMs = Math.max(0, this.currentSegmentMs - this.remainingMs);
                    this.currentSegmentElapsedSeconds = Math.floor(this.currentSegmentElapsedMs / 1000);
                    this.timeRemaining = Math.max(0, Math.ceil(this.remainingMs / 1000));

                    if (!this.isResting) {
                        this.currentBlockElapsedMs += delta;
                    }

                    this.maybePlayCountdownCue();

                    if (this.remainingMs <= 0) {
                        this.remainingMs = 0;
                        this.timeRemaining = 0;
                        requestAnimationFrame(() => this.handleSegmentComplete());
                        return;
                    }
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

    handleSegmentComplete() {
        if (this.isResting) {
            this.finishRest();
        } else {
            this.finishCurrentWorkRep();
        }

        if (this.state === 'running') {
            this.lastFrameTime = performance.now();
            this.timerHandle = requestAnimationFrame(this.tick);
        }
    },

    moveToNextItem() {
        this.phase = 'work';
        this.currentItemIndex++;
        this.currentRepeat = 1;
        this.currentBlockElapsedMs = 0;
        this.currentSegmentElapsedMs = 0;
        this.currentSegmentElapsedSeconds = 0;
        this.lastCountdownSecond = null;

        if (this.currentItemIndex >= this.items.length) {
            this.completeWorkout();
        } else {
            this.setWorkSegment(true);
        }
    },

    next() {
        if (this.state !== 'running' && this.state !== 'paused') {
            return;
        }

        if (this.isResting) {
            this.finishRest();
            return;
        }

        this.finishCurrentWorkRep();
    },

    updateExerciseCompletion(itemIndex, status, actualDuration = null) {
        const item = this.items[itemIndex];
        if (!item || !item.track_completion || !item.id) {
            return;
        }

        const exerciseData = {
            exercise_id: item.id,
            order: item.tracking_order || item.order,
            status: status
        };

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
        this.currentSegmentElapsedMs = 0;
        this.currentSegmentElapsedSeconds = 0;
        this.lastCountdownSecond = null;
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
    },

    setWorkSegment(resetBlockElapsed = false) {
        if (resetBlockElapsed) {
            this.currentBlockElapsedMs = 0;
        }

        this.phase = 'work';
        this.currentSegmentElapsedMs = 0;
        this.currentSegmentElapsedSeconds = 0;

        if (this.isCurrentSegmentManual) {
            this.currentSegmentMs = 0;
            this.remainingMs = 0;
            this.timeRemaining = 0;
            this.lastCountdownSecond = null;

            return;
        }

        this.setSegment(this.currentItem?.duration_seconds || 0);
    },

    finishCurrentWorkRep() {
        const item = this.currentItem;
        if (!item) {
            return;
        }

        const repeats = this.getRepeatCount(item);
        const actualDuration = Math.floor(this.currentBlockElapsedMs / 1000);
        const isFinalRepeat = this.currentRepeat >= repeats;

        if (isFinalRepeat) {
            this.completedItemStates[this.currentItemIndex] = 'completed';
            this.updateExerciseCompletion(this.currentItemIndex, 'completed', actualDuration);
            this.playCue('complete');
        }

        const hasTransitionRest = (item.rest_after_seconds || 0) > 0 &&
            (this.currentRepeat < repeats || this.currentItemIndex < this.items.length - 1);

        if (hasTransitionRest) {
            this.phase = 'rest';
            this.restTarget = this.currentRepeat < repeats ? 'next_repeat' : 'next_item';
            this.setSegment(item.rest_after_seconds);
            this.playCue('rest');

            return;
        }

        if (this.currentRepeat < repeats) {
            this.currentRepeat += 1;
            this.setWorkSegment(false);

            return;
        }

        this.moveToNextItem();
    },

    finishRest() {
        if (this.restTarget === 'next_repeat') {
            this.phase = 'work';
            this.currentRepeat += 1;
            this.setWorkSegment(false);

            return;
        }

        this.moveToNextItem();
    },

    getRepeatCount(item) {
        return Math.max(1, Number(item?.repeats || 1));
    },

    toggleAudio() {
        this.audioEnabled = !this.audioEnabled;

        if (this.audioEnabled) {
            this.ensureAudioContext();
        }
    },

    ensureAudioContext() {
        if (this.audioContext) {
            if (this.audioContext.state === 'suspended') {
                this.audioContext.resume();
            }

            return this.audioContext;
        }

        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) {
            return null;
        }

        this.audioContext = new AudioContextClass();
        if (this.audioContext.state === 'suspended') {
            this.audioContext.resume();
        }

        return this.audioContext;
    },

    playCue(kind) {
        if (!this.audioEnabled) {
            return;
        }

        const context = this.ensureAudioContext();
        if (!context) {
            return;
        }

        const oscillator = context.createOscillator();
        const gain = context.createGain();

        oscillator.connect(gain);
        gain.connect(context.destination);

        oscillator.type = 'sine';
        oscillator.frequency.value = kind === 'countdown' ? 880 : kind === 'rest' ? 600 : 960;
        gain.gain.value = 0.03;

        const now = context.currentTime;
        oscillator.start(now);
        oscillator.stop(now + (kind === 'complete' ? 0.18 : 0.09));
    },

    maybePlayCountdownCue() {
        if (!this.audioEnabled || this.isResting || this.isCurrentSegmentManual) {
            return;
        }

        const secondsRemaining = Math.max(0, Math.ceil(this.remainingMs / 1000));
        if (secondsRemaining > 0 && secondsRemaining <= 3 && this.lastCountdownSecond !== secondsRemaining) {
            this.lastCountdownSecond = secondsRemaining;
            this.playCue('countdown');
        }
    }
}));

Alpine.start();
