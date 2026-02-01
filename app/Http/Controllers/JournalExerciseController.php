<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJournalExerciseRequest;
use App\Http\Requests\UpdateJournalExerciseRequest;
use App\Models\JournalEntry;
use App\Models\JournalExercise;
use Illuminate\Http\RedirectResponse;

class JournalExerciseController extends Controller
{
    public function store(StoreJournalExerciseRequest $request, string $entry): RedirectResponse
    {
        if ($entry === 'new') {
            $entry = JournalEntry::firstOrCreate([
                'user_id' => auth()->id(),
                'entry_date' => auth()->user()->now()->toDateString(),
            ]);
        } else {
            $entry = JournalEntry::findOrFail($entry);
            if ($entry->user_id !== auth()->id()) {
                abort(403);
            }
        }

        $maxOrder = $entry->journalExercises()->max('order') ?? 0;

        $entry->journalExercises()->create([
            'name' => $request->name,
            'duration_minutes' => $request->duration_minutes,
            'notes' => $request->notes,
            'order' => $maxOrder + 1,
        ]);

        return redirect()->route('home')->with('success', 'Exercise added to journal successfully');
    }

    public function update(UpdateJournalExerciseRequest $request, JournalExercise $exercise): RedirectResponse
    {
        $exercise->update($request->validated());

        return redirect()->route('home')->with('success', 'Journal exercise updated successfully');
    }

    public function destroy(JournalExercise $exercise): RedirectResponse
    {
        if ($exercise->journalEntry->user_id !== auth()->id()) {
            abort(403);
        }

        $entry = $exercise->journalEntry;
        $exercise->delete();

        if ($entry->journalExercises()->count() === 0) {
            $entry->delete();

            return redirect()->route('home')->with('success', 'Journal entry deleted successfully');
        }

        return redirect()->route('home')->with('success', 'Journal exercise deleted successfully');
    }
}
