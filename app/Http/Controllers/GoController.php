<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\SessionTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoController extends Controller
{
    public function index(Request $request): View
    {
        $templateId = $request->query('template');
        $template = null;
        $exercises = collect();
        $session = null;

        if ($templateId) {
            $template = SessionTemplate::query()
                ->availableFor(auth()->user())
                ->with(['exercises' => function ($query) {
                    $query->orderByPivot('order');
                }])
                ->findOrFail($templateId);

            $exercises = $template->exercises;

            $session = Session::query()->create([
                'user_id' => auth()->id(),
                'session_template_id' => $template->id,
                'name' => $template->name,
                'status' => 'planned',
            ]);

            $exercisesData = $exercises->map(function ($ex) use ($template) {
                return [
                    'id' => $ex->id,
                    'name' => $ex->name,
                    'description' => $ex->description,
                    'sets' => $ex->pivot->sets,
                    'reps' => $ex->pivot->reps,
                    'duration_seconds' => $ex->pivot->duration_seconds ?? 0,
                    'rest_after_seconds' => $ex->pivot->rest_after_seconds ?? ($template->default_rest_seconds ?? 30),
                    'order' => $ex->pivot->order,
                ];
            })->values();
        } else {
            $exercisesData = [];
        }

        $templates = SessionTemplate::query()
            ->availableFor(auth()->user())
            ->with(['exercises' => function ($query) {
                $query->orderByPivot('order');
            }])
            ->orderBy('name')
            ->get();

        return view('go', [
            'template' => $template,
            'exercises' => $exercises,
            'exercisesData' => $exercisesData,
            'templates' => $templates,
            'session' => $session,
        ]);
    }

    public function update(Request $request, Session $session): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:planned,in_progress,completed,cancelled',
            'total_duration_seconds' => 'nullable|integer|min:0',
        ]);

        if ($session->user_id !== auth()->id()) {
            abort(403);
        }

        $updateData = [
            'status' => $validated['status'],
        ];

        if (isset($validated['total_duration_seconds'])) {
            $updateData['total_duration_seconds'] = $validated['total_duration_seconds'];
        }

        if ($validated['status'] === 'in_progress' && $session->started_at === null) {
            $updateData['started_at'] = now();
        }

        if ($validated['status'] === 'completed' && $session->completed_at === null) {
            $updateData['completed_at'] = now();
        }

        $session->update($updateData);

        return response()->json(['success' => true]);
    }
}
