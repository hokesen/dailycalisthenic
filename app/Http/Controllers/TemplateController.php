<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCustomExerciseRequest;
use App\Http\Requests\AddExerciseRequest;
use App\Http\Requests\DeleteTemplateRequest;
use App\Http\Requests\RemoveExerciseRequest;
use App\Http\Requests\SwapExerciseRequest;
use App\Http\Requests\UpdateExerciseRequest;
use App\Http\Requests\UpdateTemplateNameRequest;
use App\Models\Exercise;
use App\Models\SessionTemplate;
use App\Services\TemplateReplicationService;
use App\Support\PivotDataBuilder;
use Illuminate\Http\RedirectResponse;

class TemplateController extends Controller
{
    public function __construct(public TemplateReplicationService $replicationService) {}

    public function swapExercise(SwapExerciseRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $exercise = $template->exercises()->where('exercise_id', $request->exercise_id)->first();

        if (! $exercise) {
            return back()->with('error', 'Exercise not found in template');
        }

        $pivotData = PivotDataBuilder::fromSessionTemplateExercisePivot($exercise->pivot);

        $template->exercises()->detach($request->exercise_id);
        $template->exercises()->attach($request->new_exercise_id, $pivotData);

        return redirect()->route('dashboard')->with('success', 'Exercise swapped successfully');
    }

    public function removeExercise(RemoveExerciseRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $template->exercises()->detach($request->exercise_id);

        $this->reorderExercises($template);

        return redirect()->route('dashboard')->with('success', 'Exercise removed successfully');
    }

    public function addExercise(AddExerciseRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $maxOrder = $template->exercises()->max('session_template_exercises.order') ?? 0;

        $template->exercises()->attach(
            $request->exercise_id,
            PivotDataBuilder::defaultSessionTemplateExercisePivot($maxOrder + 1, $template->default_rest_seconds)
        );

        return redirect()->route('dashboard')->with('success', 'Exercise added successfully');
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

        return redirect()->route('dashboard')->with('success', 'Custom exercise created and added successfully');
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
        ]);

        return redirect()->route('dashboard')->with('success', 'Exercise updated successfully');
    }

    public function updateName(UpdateTemplateNameRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template = $this->ensureUserOwnsTemplate($template);

        $template->update([
            'name' => $request->name,
        ]);

        return redirect()->route('dashboard')->with('success', 'Template name updated successfully');
    }

    public function destroy(DeleteTemplateRequest $request, SessionTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('dashboard')->with('success', 'Template deleted successfully');
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
}
