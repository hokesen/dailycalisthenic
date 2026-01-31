<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteTemplateRequest;
use App\Http\Requests\UpdateTemplateNameRequest;
use App\Models\SessionTemplate;
use App\Repositories\ExerciseRepository;
use App\Services\TemplateReplicationService;
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

    protected function ensureUserOwnsTemplate(SessionTemplate $template): SessionTemplate
    {
        return $this->replicationService->ensureOwnership($template, auth()->user());
    }

    protected function redirectToTemplate(SessionTemplate $template, ?string $message = null): RedirectResponse
    {
        $redirect = redirect()->route('home', ['template' => $template->id, 'tab' => 'templates']);

        if ($message) {
            $redirect->with('success', $message);
        }

        return $redirect;
    }
}
