@props(['userNow'])

<form action="{{ route('journal.store') }}" method="POST" class="space-y-4" data-refresh-csrf>
    @csrf

    <div>
        <x-input-label for="journal_entry_date" :value="__('Date')" />
        <input
            type="date"
            name="entry_date"
            id="journal_entry_date"
            value="{{ old('entry_date', $userNow->toDateString()) }}"
            max="{{ $userNow->toDateString() }}"
            required
            class="app-input px-3 py-2.5 text-sm sm:text-base"
        >
        <x-input-error :messages="$errors->get('entry_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="journal_notes" :value="__('Note')" />
        <textarea
            name="notes"
            id="journal_notes"
            rows="4"
            class="app-input px-3 py-2.5 text-sm sm:text-base"
            placeholder="What did you work on or notice?"
            required
        >{{ old('notes') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="flex justify-end">
        <button type="submit" class="app-btn app-btn-primary">
            Submit Journal Entry
        </button>
    </div>
</form>
