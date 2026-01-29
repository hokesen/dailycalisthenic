<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCustomExerciseRequest;
use App\Http\Requests\AddExerciseRequest;
use App\Http\Requests\MoveExerciseRequest;
use App\Http\Requests\RemoveExerciseRequest;
use App\Http\Requests\SwapExerciseRequest;
use App\Http\Requests\UpdateExerciseRequest;
use App\Models\Exercise;
use App\Models\SessionTemplate;
use App\Repositories\ExerciseRepository;
use App\Services\ExerciseOrderService;
use App\Services\TemplateReplicationService;
use App\Support\PivotDataBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TemplateExerciseController extends Controller
{
    public function __construct(
        private readonly ExerciseRepository $exerciseRepository,
        private readonly ExerciseOrderService $orderService,
        private readonly TemplateReplicationService $replicationService
    ) {}

    public function swap(SwapExerciseRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $exercise = $template->exercises()
            ->where('exercise_id', $request->exercise_id)
            ->wherePivot('order', $request->order)
            ->first();

        if (! $exercise) {
            return back()->with('error', 'Exercise not found in template');
        }

        // Materialize default exercise if needed (negative ID)
        $newExerciseId = $request->new_exercise_id;
        if ($newExerciseId < 0) {
            $materialized = $this->exerciseRepository->materialize($newExerciseId);
            if (! $materialized) {
                return back()->with('error', 'Exercise not found');
            }
            $newExerciseId = $materialized->id;
        }

        $pivotData = PivotDataBuilder::fromSessionTemplateExercisePivot($exercise->pivot);

        $template->exercises()->wherePivot('order', $request->order)->detach();
        $template->exercises()->attach($newExerciseId, $pivotData);

        return $this->redirectToTemplate($template, 'Exercise swapped successfully');
    }

    public function remove(RemoveExerciseRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $template->exercises()->detach($request->exercise_id);

        $this->orderService->reorder($template);

        return $this->redirectToTemplate($template, 'Exercise removed successfully');
    }

    public function add(AddExerciseRequest $request, SessionTemplate $template): RedirectResponse|JsonResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        // Materialize default exercise if needed (negative ID)
        $exerciseId = $request->exercise_id;
        if ($exerciseId < 0) {
            $materialized = $this->exerciseRepository->materialize($exerciseId);
            if (! $materialized) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Exercise not found'], 404);
                }

                return back()->with('error', 'Exercise not found');
            }
            $exerciseId = $materialized->id;
        }

        $maxOrder = $template->exercises()->max('session_template_exercises.order') ?? 0;

        $template->exercises()->attach(
            $exerciseId,
            PivotDataBuilder::defaultSessionTemplateExercisePivot($maxOrder + 1, $template->default_rest_seconds)
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Exercise added successfully']);
        }

        return $this->redirectToTemplate($template, 'Exercise added successfully');
    }

    public function addCustom(AddCustomExerciseRequest $request, SessionTemplate $template): RedirectResponse|JsonResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $exercise = Exercise::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
        ]);

        $maxOrder = $template->exercises()->max('session_template_exercises.order') ?? 0;

        $template->exercises()->attach(
            $exercise->id,
            PivotDataBuilder::defaultSessionTemplateExercisePivot($maxOrder + 1, $template->default_rest_seconds)
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Custom exercise created and added successfully']);
        }

        return $this->redirectToTemplate($template, 'Custom exercise created and added successfully');
    }

    public function update(UpdateExerciseRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $template->exercises()->updateExistingPivot($request->exercise_id, [
            'duration_seconds' => $request->duration_seconds,
            'rest_after_seconds' => $request->rest_after_seconds,
            'sets' => $request->sets,
            'reps' => $request->reps,
            'notes' => $request->notes,
            'tempo' => $request->tempo,
            'intensity' => $request->intensity,
        ]);

        return $this->redirectToTemplate($template, 'Exercise updated successfully');
    }

    public function moveUp(MoveExerciseRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $exerciseId = $request->validated('exercise_id');

        $this->orderService->moveUp($template, $exerciseId);

        return $this->redirectToTemplate($template, 'Exercise moved up');
    }

    public function moveDown(MoveExerciseRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $exerciseId = $request->validated('exercise_id');

        $this->orderService->moveDown($template, $exerciseId);

        return $this->redirectToTemplate($template, 'Exercise moved down');
    }

    protected function ensureUserOwnsTemplate(SessionTemplate $template): SessionTemplate
    {
        return $this->replicationService->ensureOwnership($template, auth()->user());
    }

    protected function redirectToTemplate(SessionTemplate $template, ?string $message = null): RedirectResponse
    {
        $redirect = redirect()->route('home', ['template' => $template->id]);

        if ($message) {
            $redirect->with('success', $message);
        }

        return $redirect;
    }
}
