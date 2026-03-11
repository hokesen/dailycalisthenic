@props(['isDisciplineLive' => false, 'meditationDashboard' => []])

<div
    x-data="{
        state: 'idle',
        elapsedSeconds: 0,
        breathPhase: 'in',
        timerInterval: null,
        breathInterval: null,
        showHistory: false,

        get formattedTime() {
            const minutes = Math.floor(this.elapsedSeconds / 60);
            const seconds = this.elapsedSeconds % 60;
            return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        },

        get formattedMinutes() {
            return Math.floor(this.elapsedSeconds / 60);
        },

        begin() {
            this.state = 'practicing';
            this.elapsedSeconds = 0;
            this.breathPhase = 'in';

            this.timerInterval = setInterval(() => {
                this.elapsedSeconds++;
            }, 1000);

            this.breathInterval = setInterval(() => {
                this.breathPhase = this.breathPhase === 'in' ? 'out' : 'in';
            }, 4000);
        },

        endPractice() {
            clearInterval(this.timerInterval);
            clearInterval(this.breathInterval);
            this.timerInterval = null;
            this.breathInterval = null;

            const payload = {
                duration_seconds: this.elapsedSeconds,
                technique: 'breathing',
                breath_cycles_completed: Math.floor(this.elapsedSeconds / 8)
            };

            fetch('{{ route('meditation.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            this.state = 'complete';
        },

        returnToIdle() {
            this.state = 'idle';
            this.elapsedSeconds = 0;
            this.breathPhase = 'in';
        }
    }"
    class="flex flex-col items-center justify-center py-12 sm:py-20"
>
    {{-- IDLE STATE --}}
    <template x-if="state === 'idle'">
        <div class="flex flex-col items-center gap-8">
            <div class="breathe-circle">
                <span
                    class="text-sm font-medium tracking-wide text-teal-200/80 select-none"
                    x-text="breathPhase === 'in' ? 'Breathe in' : 'Breathe out'"
                    x-init="setInterval(() => { breathPhase = breathPhase === 'in' ? 'out' : 'in' }, 4000)"
                ></span>
            </div>

            <button
                @click="begin()"
                class="app-btn app-btn-primary px-8 py-3 text-base rounded-xl"
            >
                Begin
            </button>

            @if (($meditationDashboard['today_log'] ?? null) !== null)
                <p class="text-sm text-white/50">
                    You practiced today. {{ floor(($meditationDashboard['today_log']->duration_seconds ?? 0) / 60) }}m
                </p>
            @endif

            {{-- History toggle --}}
            @if (count($meditationDashboard['recent_logs'] ?? []) > 0)
                <div class="w-full max-w-sm">
                    <button
                        @click="showHistory = !showHistory"
                        class="text-sm text-white/40 hover:text-white/60 transition-colors"
                        x-text="showHistory ? 'Hide history' : 'History'"
                    ></button>

                    <div x-show="showHistory" x-transition class="mt-4 space-y-2">
                        @foreach (($meditationDashboard['recent_logs'] ?? []) as $log)
                            <div class="flex items-center justify-between rounded-lg border border-white/10 bg-white/5 px-4 py-2 text-sm">
                                <span class="text-white/60">{{ $log->practiced_at->format('M j') }}</span>
                                <span class="text-white/80">{{ floor($log->duration_seconds / 60) }}m {{ $log->duration_seconds % 60 }}s</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </template>

    {{-- PRACTICING STATE --}}
    <template x-if="state === 'practicing'">
        <div class="flex flex-col items-center gap-8">
            <div class="text-center">
                <div class="text-[4rem] font-bold leading-none text-white tabular-nums" x-text="formattedTime"></div>
                <p class="mt-2 text-sm text-white/50">Session in progress</p>
            </div>

            <div class="breathe-circle breathe-circle--active">
                <span
                    class="text-sm font-medium tracking-wide text-teal-200/80 select-none"
                    x-text="breathPhase === 'in' ? 'Breathe in' : 'Breathe out'"
                ></span>
            </div>

            <button
                @click="endPractice()"
                class="app-btn app-btn-secondary px-8 py-3 text-base rounded-xl"
            >
                End Practice
            </button>
        </div>
    </template>

    {{-- COMPLETE STATE --}}
    <template x-if="state === 'complete'">
        <div class="flex flex-col items-center gap-6 text-center">
            <h2 class="text-2xl font-semibold text-white">Practice complete.</h2>
            <p class="text-white/60" x-text="formattedMinutes + ' minutes'"></p>

            <button
                @click="returnToIdle()"
                class="app-btn app-btn-primary px-8 py-3 text-base rounded-xl"
            >
                Return
            </button>
        </div>
    </template>
</div>
