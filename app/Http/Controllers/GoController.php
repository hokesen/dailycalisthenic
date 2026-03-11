<?php

namespace App\Http\Controllers;

use App\Enums\TrainingDiscipline;
use App\Enums\SessionStatus;
use App\Http\Requests\UpdateSessionRequest;
use App\Models\Session;
use App\Models\TrainingProgramEnrollment;
use App\Models\SessionTemplate;
use App\Services\CachedProgressionAnalyticsService;
use App\Services\CachedStreakService;
use App\Services\PracticeSessionService;
use App\Services\TrainingCatalogService;
use App\Services\TrainingProgramService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoController extends Controller
{
    public function __construct(
        private CachedStreakService $streakService,
        private CachedProgressionAnalyticsService $analyticsService,
        private TrainingCatalogService $trainingCatalogService,
        private PracticeSessionService $practiceSessionService,
        private TrainingProgramService $trainingProgramService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $templateId = $request->query('template');
        $templateSlug = $request->query('template_slug');
        $discipline = (string) $request->query(
            'discipline',
            $request->session()->get('selected_discipline', TrainingDiscipline::General->value)
        );

        if (! $templateId && ! $templateSlug) {
            return redirect()->route('dashboard');
        }

        $template = $templateSlug
            ? $this->trainingCatalogService->materializeTemplate($templateSlug, $discipline)
            : SessionTemplate::query()
                ->availableFor(auth()->user())
                ->findOrFail($templateId);

        $template->load([
            'practiceBlocks' => fn ($query) => $query->with('exercise')->orderBy('sort_order'),
            'exercises' => fn ($query) => $query->orderByPivot('order'),
        ]);

        $enrollment = $this->resolveEnrollment($request);

        $session = Session::query()->create([
            'user_id' => auth()->id(),
            'session_template_id' => $template->id,
            'training_program_enrollment_id' => $enrollment?->id,
            'program_day_key' => $request->query('program_day_key'),
            'name' => $template->name,
            'status' => SessionStatus::Planned->value,
        ]);

        $this->practiceSessionService->createSessionExercises($session, $template);

        if ($enrollment && $request->filled('program_day_key') && $request->filled('scheduled_for')) {
            $this->trainingProgramService->attachDayLogToSession(
                $enrollment,
                (string) $request->query('program_day_key'),
                Carbon::parse((string) $request->query('scheduled_for')),
                $session,
            );
        }

        $practiceItems = $this->practiceSessionService->buildPracticeItems($template);

        return view('go', [
            'template' => $template,
            'practiceItems' => $practiceItems,
            'session' => $session,
            'restartUrl' => $this->buildRestartUrl($request, $template, $enrollment),
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

        if ($request->validated('status') === SessionStatus::InProgress->value && $session->completed_at !== null) {
            $updateData['completed_at'] = null;
        }

        $wasJustCompleted = false;
        if ($request->validated('status') === SessionStatus::Completed->value && $session->completed_at === null) {
            $updateData['completed_at'] = now();
            $wasJustCompleted = true;
        }

        $session->update($updateData);

        // Update session_exercises with completion status
        if ($request->has('exercise_completion')) {
            $exerciseCompletions = $request->validated('exercise_completion');
            $now = now();

            foreach ($exerciseCompletions as $completion) {
                $sessionExerciseQuery = $session->sessionExercises()
                    ->where('order', $completion['order']);

                if (! empty($completion['exercise_id'])) {
                    $sessionExerciseQuery->where('exercise_id', $completion['exercise_id']);
                }

                $sessionExercise = $sessionExerciseQuery->first();

                if ($sessionExercise) {
                    $updateExerciseData = [];

                    if ($completion['status'] === 'completed') {
                        $updateExerciseData['completed_at'] = $now;
                        // If actual duration is provided (e.g., user pressed "next" early), use it
                        if (isset($completion['duration_seconds'])) {
                            $updateExerciseData['duration_seconds'] = $completion['duration_seconds'];
                        }
                    }

                    $sessionExercise->update($updateExerciseData);
                }
            }
        }

        // Invalidate caches when session is completed
        if ($wasJustCompleted) {
            $session->loadMissing('user');
            $this->trainingProgramService->markSessionCompleted($session);
            $this->streakService->invalidateUserCache($session->user_id);
            $this->analyticsService->invalidateUserCache($session->user_id);
        }

        return response()->json(['success' => true]);
    }

    private function resolveEnrollment(Request $request): ?TrainingProgramEnrollment
    {
        if (! $request->filled('program_enrollment')) {
            return null;
        }

        return TrainingProgramEnrollment::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail((int) $request->query('program_enrollment'));
    }

    private function buildRestartUrl(Request $request, SessionTemplate $template, ?TrainingProgramEnrollment $enrollment): string
    {
        $parameters = [];

        if ($request->filled('template_slug')) {
            $parameters['template_slug'] = $request->query('template_slug');
            $parameters['discipline'] = $request->query('discipline', $template->discipline?->value ?? TrainingDiscipline::General->value);
        } else {
            $parameters['template'] = $template->id;
        }

        if ($enrollment && $request->filled('program_day_key') && $request->filled('scheduled_for')) {
            $parameters['program_enrollment'] = $enrollment->id;
            $parameters['program_day_key'] = $request->query('program_day_key');
            $parameters['scheduled_for'] = $request->query('scheduled_for');
        }

        return route('go.index', $parameters);
    }
}
