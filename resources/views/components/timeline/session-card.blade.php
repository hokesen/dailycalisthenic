@props(['session'])

<div class="bg-white dark:bg-gray-800 border-l-4 border-blue-500 rounded-lg p-4 shadow-sm">
    <div class="flex justify-between items-start mb-2">
        <div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                {{ $session->name }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $session->completed_at->format('g:i A') }} • {{ round($session->total_duration_seconds / 60) }}m total
            </p>
        </div>
        <span class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded font-medium">
            Session
        </span>
    </div>

    @if($session->sessionExercises->isNotEmpty())
        <div class="space-y-1 mb-3">
            @foreach($session->sessionExercises as $se)
                <div class="text-sm">
                    <span class="text-gray-700 dark:text-gray-300">{{ $se->exercise->name }}</span>
                    @if($se->duration_seconds)
                        <span class="text-gray-500 dark:text-gray-400">• {{ round($se->duration_seconds / 60) }}m</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <x-timeline.inline-notes
        :model="$session"
        :notes="$session->notes"
        updateRoute="sessions.update-notes"
    />
</div>
