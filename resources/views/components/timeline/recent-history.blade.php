@props(['recentHistory'])

@php
    $totals = $recentHistory['totals'] ?? [];
    $days = $recentHistory['days'] ?? [];
    $maxDaySeconds = max(1, (int) ($recentHistory['maxDaySeconds'] ?? 1));
@endphp

<section class="app-panel rounded-2xl p-4 sm:p-6 mb-6 app-reveal">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <h3 class="app-section-title">Recent History</h3>
            <p class="text-sm text-white/60">A quick 14-day scan. Tap any day to jump to that date in your timeline.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="history-stat">{{ $totals['activeDays'] ?? 0 }} active days</span>
            <span class="history-stat">{{ $totals['sessionCount'] ?? 0 }} sessions</span>
            <span class="history-stat">{{ $totals['journalCount'] ?? 0 }} journals</span>
            <span class="history-stat"><x-duration-display :seconds="$totals['totalSeconds'] ?? 0" /></span>
        </div>
    </div>

    <div class="history-track">
        @foreach (collect($days)->reverse()->values() as $day)
            @php
                $intensity = $day['totalSeconds'] > 0 ? max(4, min(100, (int) round(($day['totalSeconds'] / $maxDaySeconds) * 100))) : 0;
                $topExercises = collect($day['topExercises'] ?? [])->filter()->implode(' · ');
            @endphp
            <a
                href="#timeline-day-{{ $day['isoDate'] }}"
                class="history-day {{ $day['hasActivity'] ? 'is-active' : '' }} {{ $day['isToday'] ? 'is-today' : '' }}"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold tracking-wide uppercase {{ $day['isToday'] ? 'text-emerald-300' : 'text-white/65' }}">
                        {{ $day['dayName'] }}
                    </span>
                    <span class="text-xs {{ $day['isToday'] ? 'text-emerald-300/90' : 'text-white/45' }}">
                        {{ $day['dayOfMonth'] }}
                    </span>
                </div>

                <div class="history-meter">
                    @if ($day['hasActivity'])
                        <div class="history-meter-fill" style="transform: scaleX({{ $intensity / 100 }});"></div>
                    @endif
                </div>

                @if ($day['hasActivity'])
                    <div class="mt-2 text-xs text-white/80">
                        <x-duration-display :seconds="$day['totalSeconds']" />
                    </div>
                    <div class="mt-1 text-[11px] text-white/50">
                        {{ $day['sessionCount'] }} sessions · {{ $day['journalCount'] }} journals
                    </div>
                    <div class="mt-1 text-[11px] text-white/45 truncate" title="{{ $topExercises ?: 'Journal-only activity' }}">
                        {{ $topExercises ?: 'Journal-only activity' }}
                    </div>
                @else
                    <div class="mt-2 text-xs text-white/45">No activity</div>
                @endif
            </a>
        @endforeach
    </div>
</section>
