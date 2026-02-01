@props(['templates', 'todayEntry' => null])

<div class="mb-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Today's Practice</h3>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3" x-data="{ showJournalForm: false }">
        @foreach($templates as $template)
            <a
                href="{{ route('go.index', ['template' => $template->id]) }}"
                class="block p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border border-blue-200 dark:border-blue-800 rounded-lg hover:shadow-lg transition-all group"
            >
                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ $template->name }}</h4>
                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $template->exercises->count() }} exercises</p>
                <p class="text-xs text-blue-600 dark:text-blue-400 mt-2 font-medium group-hover:underline">Start Practice →</p>
            </a>
        @endforeach

        <button
            @click="showJournalForm = !showJournalForm"
            type="button"
            class="block p-4 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border border-green-200 dark:border-green-800 rounded-lg hover:shadow-lg transition-all group text-left"
        >
            <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Journal</h4>
            <p class="text-xs text-gray-600 dark:text-gray-400">Quick journal entry</p>
            <p class="text-xs text-green-600 dark:text-green-400 mt-2 font-medium group-hover:underline">Add Entry →</p>
        </button>

        <div x-show="showJournalForm" x-collapse class="col-span-2 sm:col-span-3 lg:col-span-4">
            <x-timeline.journal-form :todayEntry="$todayEntry" />
        </div>
    </div>
</div>
