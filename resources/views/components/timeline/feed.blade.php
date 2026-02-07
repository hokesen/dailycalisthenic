@props(['timelineFeed', 'timezone', 'userNow'])

@if($timelineFeed->isEmpty())
    <div class="text-center py-12 app-panel rounded-2xl">
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <h3 class="text-lg font-semibold text-white mb-2">No activity logged yet</h3>
        <p class="text-white/60 mb-4">Start your first practice session or log a journal entry to begin tracking your progress.</p>
    </div>
@else
    @foreach($timelineFeed as $date => $items)
        <div class="mb-6">
            <h3 class="app-meta mb-3 flex items-center gap-2">
                {{ \Carbon\Carbon::parse($date, $timezone)->format('F j, Y') }}
                @if(\Carbon\Carbon::parse($date, $timezone)->isSameDay($userNow))
                    <span class="app-chip">Today</span>
                @endif
            </h3>
            <div class="space-y-3">
                @foreach($items as $item)
                    @if($item['type'] === 'session')
                        <x-timeline.session-card :session="$item['data']" />
                    @else
                        <x-timeline.journal-card :entry="$item['data']" />
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
@endif
