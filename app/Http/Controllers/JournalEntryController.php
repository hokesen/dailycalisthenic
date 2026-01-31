<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJournalEntryRequest;
use App\Http\Requests\UpdateJournalEntryRequest;
use App\Models\JournalEntry;
use Illuminate\Http\RedirectResponse;

class JournalEntryController extends Controller
{
    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $entry = JournalEntry::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'entry_date' => now()->toDateString(),
            ],
            [
                'notes' => $request->notes,
            ]
        );

        if (! $entry->wasRecentlyCreated) {
            $entry->update([
                'notes' => $request->notes,
            ]);
        }

        return redirect()->route('home')->with('success', 'Journal entry saved successfully');
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $entry): RedirectResponse
    {
        $entry->update([
            'notes' => $request->notes,
        ]);

        return redirect()->route('home')->with('success', 'Journal entry updated successfully');
    }

    public function destroy(JournalEntry $entry): RedirectResponse
    {
        if ($entry->user_id !== auth()->id()) {
            abort(403);
        }

        $entry->delete();

        return redirect()->route('home')->with('success', 'Journal entry deleted successfully');
    }
}
