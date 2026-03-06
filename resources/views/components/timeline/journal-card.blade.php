@props(['entry'])

@php
    $entryDateIso = $entry->entry_date->toDateString();
    $todayIso = auth()->user()->now()->toDateString();
    $canEditDate = $entryDateIso < $todayIso;
@endphp

<div class="app-card rounded-xl p-4 border-l-4 border-cyan-400">
    <div class="flex justify-between items-start mb-2">
        <div>
            <h4 class="font-semibold text-white">
                Journal Entry
            </h4>
            <p class="text-sm text-white/60">
                {{ \Carbon\Carbon::parse($entry->entry_date)->format('F j, Y') }}
                @if($entry->journalExercises->isNotEmpty())
                    • {{ $entry->journalExercises->sum('duration_minutes') }}m total
                @endif
            </p>

            @if($canEditDate)
                <div
                    x-data="{
                        editingDate: false,
                        entryDate: @js($entryDateIso),
                        originalDate: @js($entryDateIso),
                        savingDate: false,
                        dateError: '',
                        async saveDate() {
                            this.savingDate = true;
                            this.dateError = '';

                            try {
                                const response = await fetch('{{ route('journal.update', $entry) }}', {
                                    method: 'PATCH',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({ entry_date: this.entryDate })
                                });

                                if (!response.ok) {
                                    const data = await response.json().catch(() => ({}));
                                    this.dateError = data.errors?.entry_date?.[0] ?? 'Failed to update entry date.';
                                    throw new Error(this.dateError);
                                }

                                window.location.reload();
                            } catch (error) {
                                console.error('Error:', error);
                                this.savingDate = false;
                            }
                        },
                        cancelDateEdit() {
                            this.editingDate = false;
                            this.entryDate = this.originalDate;
                            this.dateError = '';
                        }
                    }"
                    class="mt-3"
                >
                    <div x-show="!editingDate">
                        <button
                            type="button"
                            @click="editingDate = true"
                            class="text-xs font-semibold uppercase tracking-wide text-cyan-300 hover:text-cyan-200"
                        >
                            Change date
                        </button>
                    </div>

                    <div x-show="editingDate" class="space-y-2">
                        <label class="block text-xs uppercase tracking-wide text-white/40">Entry date</label>
                        <input
                            type="date"
                            x-model="entryDate"
                            max="{{ $todayIso }}"
                            class="app-input px-3 py-2.5 text-sm sm:text-base"
                        >
                        <p x-show="dateError" x-text="dateError" class="text-xs text-red-300"></p>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="saveDate()"
                                :disabled="savingDate"
                                class="app-btn app-btn-primary"
                            >
                                <span x-show="!savingDate">Save date</span>
                                <span x-show="savingDate">Saving...</span>
                            </button>
                            <button
                                type="button"
                                @click="cancelDateEdit()"
                                :disabled="savingDate"
                                class="app-btn app-btn-secondary"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <span class="app-chip app-chip--warm">
            Journal
        </span>
    </div>

    @if($entry->journalExercises->isNotEmpty())
        <div class="space-y-1 mb-3">
            @foreach($entry->journalExercises as $je)
                <div class="text-sm flex justify-between items-start">
                    <div>
                        <span class="text-white/80">{{ $je->name }}</span>
                        @if($je->duration_minutes)
                            <span class="text-white/50">• {{ $je->duration_minutes }}m</span>
                        @endif
                        @if($je->notes)
                            <p class="text-xs text-white/50 mt-1">{{ $je->notes }}</p>
                        @endif
                    </div>
                    <form action="{{ route('journal.exercises.destroy', $je) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            onclick="return confirm('Delete this exercise?')"
                            class="text-white/40 hover:text-red-300 ml-2"
                            title="Delete exercise"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    <x-timeline.inline-notes
        :model="$entry"
        :notes="$entry->notes"
        updateRoute="journal.update"
    />
</div>
