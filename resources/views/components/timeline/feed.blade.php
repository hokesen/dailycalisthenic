@props(['timelineFeed'])

@if($timelineFeed->isEmpty())
    <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No activity logged yet</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-4">Start your first practice session or log a journal entry to begin tracking your progress.</p>
    </div>
@else
    @foreach($timelineFeed as $date => $items)
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}
                @if(\Carbon\Carbon::parse($date)->isToday())
                    <span class="text-xs bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 px-2 py-0.5 rounded">Today</span>
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
