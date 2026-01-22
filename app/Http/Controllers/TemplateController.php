<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCustomExerciseRequest;
use App\Http\Requests\AddExerciseRequest;
use App\Http\Requests\DeleteTemplateRequest;
use App\Http\Requests\MoveExerciseRequest;
use App\Http\Requests\RemoveExerciseRequest;
use App\Http\Requests\SwapExerciseRequest;
use App\Http\Requests\UpdateExerciseRequest;
use App\Http\Requests\UpdateTemplateNameRequest;
use App\Models\Exercise;
use App\Models\SessionTemplate;
use App\Repositories\ExerciseRepository;
use App\Services\TemplateReplicationService;
use App\Support\PivotDataBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function __construct(
        public TemplateReplicationService $replicationService,
        public ExerciseRepository $exerciseRepository
    ) {}

    public function store(): SessionTemplate|RedirectResponse
    {
        $template = SessionTemplate::create([
            'user_id' => auth()->id(),
            'name' => 'New Template',
            'is_public' => false,
        ]);

        if (request()->wantsJson()) {
            return $template;
        }

        return $this->redirectToTemplate($template, 'Template created! Add exercises to get started.');
    }

    public function card(SessionTemplate $template): View
    {
        $template->load(['user', 'exercises' => fn ($q) => $q->orderByPivot('order')]);

        $allExercises = $this->exerciseRepository->getAvailableForUser(auth()->user());

        return view('components.template-card', [
            'template' => $template,
            'allExercises' => $allExercises,
        ]);
    }

    public function swapExercise(SwapExerciseRequest $request, SessionTemplate $template): RedirectResponse
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

    public function removeExercise(RemoveExerciseRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $template->exercises()->detach($request->exercise_id);

        $this->reorderExercises($template);

        return $this->redirectToTemplate($template, 'Exercise removed successfully');
    }

    public function addExercise(AddExerciseRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        // Materialize default exercise if needed (negative ID)
        $exerciseId = $request->exercise_id;
        if ($exerciseId < 0) {
            $materialized = $this->exerciseRepository->materialize($exerciseId);
            if (! $materialized) {
                return back()->with('error', 'Exercise not found');
            }
            $exerciseId = $materialized->id;
        }

        $maxOrder = $template->exercises()->max('session_template_exercises.order') ?? 0;

        $template->exercises()->attach(
            $exerciseId,
            PivotDataBuilder::defaultSessionTemplateExercisePivot($maxOrder + 1, $template->default_rest_seconds)
        );

        return $this->redirectToTemplate($template, 'Exercise added successfully');
    }

    public function addCustomExercise(AddCustomExerciseRequest $request, SessionTemplate $template): RedirectResponse
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

        return $this->redirectToTemplate($template, 'Custom exercise created and added successfully');
    }

    public function updateExercise(UpdateExerciseRequest $request, SessionTemplate $template): RedirectResponse
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

    public function updateName(UpdateTemplateNameRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $template->update([
            'name' => $request->name,
        ]);

        return $this->redirectToTemplate($template, 'Template name updated successfully');
    }

    public function toggleVisibility(SessionTemplate $template): RedirectResponse
    {
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        $template->update([
            'is_public' => ! $template->is_public,
        ]);

        $status = $template->is_public ? 'public' : 'private';

        return $this->redirectToTemplate($template, "Template is now {$status}");
    }

    public function destroy(DeleteTemplateRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('home')->with('success', 'Template deleted successfully');
    }

    public function copy(SessionTemplate $template): RedirectResponse
    {
        $newTemplate = $this->replicationService->replicateForUser($template, auth()->user());

        return $this->redirectToTemplate($newTemplate, 'Template copied successfully');
    }

    public function moveExerciseUp(MoveExerciseRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $exerciseId = $request->validated('exercise_id');

        $currentExercise = $template->exercises->firstWhere('id', $exerciseId);

        if (! $currentExercise || $currentExercise->pivot->order <= 1) {
            return $this->redirectToTemplate($template);
        }

        $currentOrder = $currentExercise->pivot->order;
        $previousExercise = $template->exercises->firstWhere('pivot.order', $currentOrder - 1);

        if ($previousExercise) {
            // Use a temporary order to avoid unique constraint violation
            $tempOrder = 9999;
            $template->exercises()->updateExistingPivot($currentExercise->id, ['order' => $tempOrder]);
            $template->exercises()->updateExistingPivot($previousExercise->id, ['order' => $currentOrder]);
            $template->exercises()->updateExistingPivot($currentExercise->id, ['order' => $currentOrder - 1]);
        }

        return $this->redirectToTemplate($template, 'Exercise moved up');
    }

    public function moveExerciseDown(MoveExerciseRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $exerciseId = $request->validated('exercise_id');

        $currentExercise = $template->exercises->firstWhere('id', $exerciseId);

        if (! $currentExercise || $currentExercise->pivot->order >= $template->exercises->count()) {
            return $this->redirectToTemplate($template);
        }

        $currentOrder = $currentExercise->pivot->order;
        $nextExercise = $template->exercises->firstWhere('pivot.order', $currentOrder + 1);

        if ($nextExercise) {
            // Use a temporary order to avoid unique constraint violation
            $tempOrder = 9999;
            $template->exercises()->updateExistingPivot($currentExercise->id, ['order' => $tempOrder]);
            $template->exercises()->updateExistingPivot($nextExercise->id, ['order' => $currentOrder]);
            $template->exercises()->updateExistingPivot($currentExercise->id, ['order' => $currentOrder + 1]);
        }

        return $this->redirectToTemplate($template, 'Exercise moved down');
    }

    protected function ensureUserOwnsTemplate(SessionTemplate $template): SessionTemplate
    {
        return $this->replicationService->ensureOwnership($template, auth()->user());
    }

    protected function reorderExercises(SessionTemplate $template): void
    {
        $exercises = $template->exercises()->orderByPivot('order')->get();

        foreach ($exercises as $index => $exercise) {
            $template->exercises()->updateExistingPivot($exercise->id, [
                'order' => $index + 1,
            ]);
        }
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
