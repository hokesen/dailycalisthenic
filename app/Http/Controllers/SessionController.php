<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSessionNotesRequest;
use App\Models\Session;
use App\Models\SessionExercise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function updateNotes(UpdateSessionNotesRequest $request, Session $session): RedirectResponse
    {
        $session->update([
            'notes' => $request->notes,
        ]);

        return redirect()->route('home')->with('success', 'Session notes updated successfully');
    }

    public function updateExerciseNotes(Request $request, Session $session, SessionExercise $sessionExercise): RedirectResponse
    {
        if ($session->user_id !== auth()->id()) {
            abort(403);
        }

        if ($sessionExercise->session_id !== $session->id) {
            abort(404);
        }

        $request->validate([
            'notes' => 'nullable|string|max:10000',
        ]);

        $sessionExercise->update([
            'notes' => $request->notes,
        ]);

        return redirect()->route('home')->with('success', 'Exercise notes updated successfully');
    }
}
