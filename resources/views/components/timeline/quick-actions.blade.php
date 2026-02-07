@props(['templates', 'todayEntry' => null])

<div class="mb-6">
    <h3 class="app-section-title mb-4">Today's Practice</h3>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3" x-data="{ showJournalForm: false }">
        @foreach($templates as $template)
            <a
                href="{{ route('go.index', ['template' => $template->id]) }}"
                class="block p-4 app-card rounded-xl group hover:shadow-[0_20px_40px_-28px_rgba(39,194,176,0.6)]"
            >
                <h4 class="font-semibold text-white mb-1">{{ $template->name }}</h4>
                <p class="text-xs text-white/60">{{ $template->exercises->count() }} exercises</p>
                <p class="text-xs text-emerald-300 mt-2 font-medium group-hover:underline">Start Practice →</p>
            </a>
        @endforeach

        <button
            @click="showJournalForm = !showJournalForm"
            type="button"
            class="block p-4 app-card rounded-xl group text-left hover:shadow-[0_20px_40px_-28px_rgba(100,226,214,0.6)]"
        >
            <h4 class="font-semibold text-white mb-1">Journal</h4>
            <p class="text-xs text-white/60">Quick journal entry</p>
            <p class="text-xs text-cyan-300 mt-2 font-medium group-hover:underline">Add Entry →</p>
        </button>

        <div x-show="showJournalForm" x-transition class="col-span-2 sm:col-span-3 lg:col-span-4">
            <x-timeline.journal-form :todayEntry="$todayEntry" />
        </div>
    </div>
</div>
