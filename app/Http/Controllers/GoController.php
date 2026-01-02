<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\SessionExerciseData;
use App\Enums\SessionStatus;
use App\Http\Requests\UpdateSessionRequest;
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
        $exercisesData = [];
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
                'status' => SessionStatus::Planned->value,
            ]);

            // Create session_exercises records to preserve the exact exercises in this session
            foreach ($exercises as $exercise) {
                $session->sessionExercises()->create([
                    'exercise_id' => $exercise->id,
                    'order' => $exercise->pivot->order,
                    'duration_seconds' => $exercise->pivot->duration_seconds ?? 0,
                ]);
            }

            $exercisesData = $exercises->map(fn ($ex) => SessionExerciseData::fromTemplateExercise($ex, $template)->toArray())->values();
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
            'exercises' => $template?->exercises ?? collect(),
            'exercisesData' => $exercisesData,
            'templates' => $templates,
            'session' => $session,
        ]);
    }

    public function update(UpdateSessionRequest $request, Session $session): JsonResponse
    {
        $updateData = [
            'status' => $request->validated('status'),
        ];

        if ($request->has('total_duration_seconds')) {
            $updateData['total_duration_seconds'] = $request->validated('total_duration_seconds');
        }

        if ($request->validated('status') === SessionStatus::InProgress->value && $session->started_at === null) {
            $updateData['started_at'] = now();
        }

        if ($request->validated('status') === SessionStatus::Completed->value && $session->completed_at === null) {
            $updateData['completed_at'] = now();
        }

        $session->update($updateData);

        // Update session_exercises with completion status
        if ($request->has('exercise_completion')) {
            $exerciseCompletions = $request->validated('exercise_completion');
            $now = now();

            foreach ($exerciseCompletions as $completion) {
                $sessionExercise = $session->sessionExercises()
                    ->where('exercise_id', $completion['exercise_id'])
                    ->where('order', $completion['order'])
                    ->first();

                if ($sessionExercise) {
                    $updateExerciseData = [];

                    if ($completion['status'] === 'completed') {
                        $updateExerciseData['completed_at'] = $now;
                    }

                    $sessionExercise->update($updateExerciseData);
                }
            }
        }

        return response()->json(['success' => true]);
    }
}
