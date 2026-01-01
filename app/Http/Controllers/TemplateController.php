<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\SessionTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function swapExercise(Request $request, SessionTemplate $template): RedirectResponse
    {
        $request->validate([
            'exercise_id' => 'required|exists:exercises,id',
            'new_exercise_id' => 'required|exists:exercises,id',
        ]);

        $template = $this->ensureUserOwnsTemplate($template);

        $exercise = $template->exercises()->where('exercise_id', $request->exercise_id)->first();

        if (! $exercise) {
            return back()->with('error', 'Exercise not found in template');
        }

        $pivotData = [
            'order' => $exercise->pivot->order,
            'duration_seconds' => $exercise->pivot->duration_seconds,
            'rest_after_seconds' => $exercise->pivot->rest_after_seconds,
            'sets' => $exercise->pivot->sets,
            'reps' => $exercise->pivot->reps,
            'notes' => $exercise->pivot->notes,
        ];

        $template->exercises()->detach($request->exercise_id);
        $template->exercises()->attach($request->new_exercise_id, $pivotData);

        return redirect()->route('dashboard')->with('success', 'Exercise swapped successfully');
    }

    public function removeExercise(Request $request, SessionTemplate $template): RedirectResponse
    {
        $request->validate([
            'exercise_id' => 'required|exists:exercises,id',
        ]);

        $template = $this->ensureUserOwnsTemplate($template);

        $template->exercises()->detach($request->exercise_id);

        $this->reorderExercises($template);

        return redirect()->route('dashboard')->with('success', 'Exercise removed successfully');
    }

    public function addExercise(Request $request, SessionTemplate $template): RedirectResponse
    {
        $request->validate([
            'exercise_id' => 'required|exists:exercises,id',
        ]);

        $template = $this->ensureUserOwnsTemplate($template);

        $maxOrder = $template->exercises()->max('session_template_exercises.order') ?? 0;

        $template->exercises()->attach($request->exercise_id, [
            'order' => $maxOrder + 1,
            'duration_seconds' => null,
            'rest_after_seconds' => $template->default_rest_seconds,
            'sets' => null,
            'reps' => null,
            'notes' => null,
        ]);

        return redirect()->route('dashboard')->with('success', 'Exercise added successfully');
    }

    public function addCustomExercise(Request $request, SessionTemplate $template): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $template = $this->ensureUserOwnsTemplate($template);

        $exercise = Exercise::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
        ]);

        $maxOrder = $template->exercises()->max('session_template_exercises.order') ?? 0;

        $template->exercises()->attach($exercise->id, [
            'order' => $maxOrder + 1,
            'duration_seconds' => null,
            'rest_after_seconds' => $template->default_rest_seconds,
            'sets' => null,
            'reps' => null,
            'notes' => null,
        ]);

        return redirect()->route('dashboard')->with('success', 'Custom exercise created and added successfully');
    }

    public function updateExercise(Request $request, SessionTemplate $template): RedirectResponse
    {
        $request->validate([
            'exercise_id' => 'required|exists:exercises,id',
            'duration_seconds' => 'nullable|integer|min:0',
            'rest_after_seconds' => 'nullable|integer|min:0',
            'sets' => 'nullable|integer|min:1',
            'reps' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

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

    public function updateName(Request $request, SessionTemplate $template): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $template = $this->ensureUserOwnsTemplate($template);

        $template->update([
            'name' => $request->name,
        ]);

        return redirect()->route('dashboard')->with('success', 'Template name updated successfully');
    }

    protected function ensureUserOwnsTemplate(SessionTemplate $template): SessionTemplate
    {
        if ($template->user_id === auth()->id()) {
            return $template;
        }

        $newTemplate = $template->replicate();
        $newTemplate->user_id = auth()->id();
        $newTemplate->name = auth()->user()->name."'s ".$template->name;
        $newTemplate->save();

        foreach ($template->exercises as $exercise) {
            $newTemplate->exercises()->attach($exercise->id, [
                'order' => $exercise->pivot->order,
                'duration_seconds' => $exercise->pivot->duration_seconds,
                'rest_after_seconds' => $exercise->pivot->rest_after_seconds,
                'sets' => $exercise->pivot->sets,
                'reps' => $exercise->pivot->reps,
                'notes' => $exercise->pivot->notes,
            ]);
        }

        return $newTemplate;
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
