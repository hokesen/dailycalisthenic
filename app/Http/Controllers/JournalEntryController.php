<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJournalEntryRequest;
use App\Http\Requests\UpdateJournalEntryRequest;
use App\Models\JournalEntry;
use App\Services\CachedStreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class JournalEntryController extends Controller
{
    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $entryDate = $request->validated('entry_date') ?? auth()->user()->now()->toDateString();
        $userId = auth()->id();

        JournalEntry::create([
            'user_id' => $userId,
            'entry_date' => $entryDate,
            'notes' => $request->notes,
        ]);

        app(CachedStreakService::class)->invalidateUserCache($userId);

        return redirect()->route('home')->with('success', 'Journal entry saved successfully');
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $entry): RedirectResponse|JsonResponse
    {
        $entry->update($request->validated());

        app(CachedStreakService::class)->invalidateUserCache($entry->user_id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('home')->with('success', 'Journal entry updated successfully');
    }
}
