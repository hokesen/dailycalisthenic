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
        $today = auth()->user()->now()->toDateString();
        $userId = auth()->id();

        $entry = JournalEntry::where('user_id', $userId)
            ->whereDate('entry_date', $today)
            ->first();

        if ($entry) {
            $entry->update(['notes' => $request->notes]);
        } else {
            JournalEntry::create([
                'user_id' => $userId,
                'entry_date' => $today,
                'notes' => $request->notes,
            ]);
        }

        app(CachedStreakService::class)->invalidateUserCache($userId);

        return redirect()->route('home')->with('success', 'Journal entry saved successfully');
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $entry): RedirectResponse|JsonResponse
    {
        $entry->update([
            'notes' => $request->notes,
        ]);

        app(CachedStreakService::class)->invalidateUserCache($entry->user_id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('home')->with('success', 'Journal entry updated successfully');
    }
}
