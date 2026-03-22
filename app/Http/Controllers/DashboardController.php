<?php

namespace App\Http\Controllers;

use App\Enums\TrainingDiscipline;
use App\Models\JournalEntry;
use App\Models\Session;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Repositories\ExerciseRepository;
use App\Services\AssessmentService;
use App\Services\LiftingDashboardService;
use App\Services\MeditationDashboardService;
use App\Services\StarterTemplateService;
use App\Services\StreakService;
use App\Services\TrainingCatalogService;
use App\Services\TrainingProgramService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ExerciseRepository $exerciseRepository,
        private readonly StreakService $streakService,
        private readonly StarterTemplateService $starterTemplateService,
        private readonly TrainingCatalogService $trainingCatalogService,
        private readonly TrainingProgramService $trainingProgramService,
        private readonly AssessmentService $assessmentService,
        private readonly MeditationDashboardService $meditationDashboardService,
        private readonly LiftingDashboardService $liftingDashboardService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (! auth()->check()) {
            if ($request->has('template') || $request->has('template_slug')) {
                return redirect()->guest(route('login'));
            }

            return view('welcome');
        }

        $this->starterTemplateService->ensureStarterTemplates();

        /** @var User $user */
        $user = auth()->user();
        $disciplines = $this->trainingCatalogService->getDisciplines();
        $selectedDiscipline = (string) $request->session()->get('selected_discipline', TrainingDiscipline::General->value);

        if (! array_key_exists($selectedDiscipline, $disciplines)) {
            $selectedDiscipline = TrainingDiscipline::General->value;
            $request->session()->put('selected_discipline', $selectedDiscipline);
        }

        $isGeneralDiscipline = $selectedDiscipline === TrainingDiscipline::General->value;
        $isSoccerDiscipline = $selectedDiscipline === TrainingDiscipline::Soccer->value;
        $isMeditationDiscipline = $selectedDiscipline === TrainingDiscipline::Meditation->value;

        $days = (int) $request->query('days', 7);
        $days = in_array($days, [7, 14, 30, 90], true) ? $days : 7;

        $userTimezone = $user->preferredTimezone();
        $userNow = $user->now();
        $startDate = $userNow->copy()->subDays($days - 1)->startOfDay();
        $endDate = $userNow->copy()->endOfDay();
        $startDateUtc = $startDate->copy()->timezone('UTC');
        $endDateUtc = $endDate->copy()->timezone('UTC');

        $userUsedExercises = $this->loadUserUsedExercises($user, $selectedDiscipline);
        $availableExercises = $this->exerciseRepository
            ->getAvailableForUser($user)
            ->filter(function ($exercise) use ($selectedDiscipline) {
                $discipline = $exercise->discipline?->value ?? $exercise->discipline ?? TrainingDiscipline::General->value;

                return $discipline === $selectedDiscipline;
            });

        $allExercises = $userUsedExercises->merge(
            $availableExercises->filter(fn ($exercise) => ! $userUsedExercises->contains('id', $exercise->id) &&
                ! $userUsedExercises->contains('name', $exercise->name)
            )
        )->sortBy('name')->values();

        $authUserTemplates = SessionTemplate::query()
            ->where('user_id', $user->id)
            ->where('discipline', $selectedDiscipline)
            ->with([
                'user',
                'practiceBlocks.exercise',
                'exercises' => function ($query) {
                    $query->with(['progression.easierExercise', 'progression.harderExercise'])
                        ->orderByPivot('order');
                },
            ])
            ->withSum(['sessions' => function ($query) use ($user, $startDateUtc, $endDateUtc) {
                $query->where('user_id', $user->id)
                    ->completed()
                    ->whereBetween('completed_at', [$startDateUtc, $endDateUtc]);
            }], 'total_duration_seconds')
            ->orderByDesc('sessions_sum_total_duration_seconds')
            ->get();

        $systemTemplates = SessionTemplate::query()
            ->whereNull('user_id')
            ->where('is_public', true)
            ->where('discipline', $selectedDiscipline)
            ->with([
                'user',
                'practiceBlocks.exercise',
                'exercises' => function ($query) {
                    $query->with(['progression.easierExercise', 'progression.harderExercise'])
                        ->orderByPivot('order');
                },
            ])
            ->get();

        $leaderboardEntries = $isGeneralDiscipline
            ? $this->buildGeneralLeaderboardData($user)
            : collect();

        $todayStartUtc = $userNow->copy()->startOfDay()->timezone('UTC');
        $todayEndUtc = $userNow->copy()->endOfDay()->timezone('UTC');

        $hasPracticedToday = $this->disciplineSessionQuery($user, $selectedDiscipline)
            ->completed()
            ->whereBetween('completed_at', [$todayStartUtc, $todayEndUtc])
            ->exists();

        $selectedTemplateId = $request->query('template');
        $initialTemplateIndex = 0;

        if ($selectedTemplateId && $authUserTemplates->isNotEmpty()) {
            $index = $authUserTemplates->search(fn ($template) => (string) $template->id === (string) $selectedTemplateId);
            if ($index !== false) {
                $initialTemplateIndex = $index;
            }
        }

        $journalEntries = JournalEntry::query()
            ->where('user_id', $user->id)
            ->whereNotNull('notes')
            ->where('notes', '!=', '')
            ->orderByDesc('entry_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        $soccerDashboard = $isSoccerDiscipline ? $this->buildSoccerDashboardData($user, $userNow) : null;
        $isMeditationDiscipline = $selectedDiscipline === TrainingDiscipline::Meditation->value;
        $isLiftingDiscipline = $selectedDiscipline === TrainingDiscipline::Lifting->value;
        $meditationDashboard = $isMeditationDiscipline ? $this->meditationDashboardService->buildDashboardData($user, $userNow) : null;
        $liftingDashboard = $isLiftingDiscipline ? $this->liftingDashboardService->buildDashboardData($user, $userNow) : null;

        return view('dashboard', [
            'allExercises' => $allExercises,
            'authUserStreak' => $this->streakService->calculateStreak($user),
            'potentialStreak' => $this->streakService->calculatePotentialStreak($user),
            'initialTemplateIndex' => $initialTemplateIndex,
            'selectedTemplateId' => $selectedTemplateId,
            'hasPracticedToday' => $hasPracticedToday,
            'journalEntries' => $journalEntries,
            'leaderboardEntries' => $leaderboardEntries,
            'userTimezone' => $userTimezone,
            'userNow' => $userNow,
            'userTemplates' => $authUserTemplates,
            'days' => $days,
            'systemTemplates' => $systemTemplates,
            'disciplines' => $disciplines,
            'selectedDiscipline' => $selectedDiscipline,
            'isDisciplineLive' => $this->trainingCatalogService->disciplineIsLive($selectedDiscipline),
            'soccerDashboard' => $soccerDashboard,
            'meditationDashboard' => $meditationDashboard,
            'liftingDashboard' => $liftingDashboard,
        ]);
    }

    private function loadUserUsedExercises(User $user, string $discipline): Collection
    {
        $templateExercises = \App\Models\Exercise::query()
            ->where('discipline', $discipline)
            ->whereHas('sessionTemplates', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        $sessionExercises = \App\Models\Exercise::query()
            ->where('discipline', $discipline)
            ->whereHas('sessions', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        return $templateExercises->merge($sessionExercises)->unique('id');
    }

    private function buildGeneralLeaderboardData(User $user): Collection
    {
        $users = User::query()
            ->where('id', '!=', $user->id)
            ->whereHas('sessionTemplates', function ($query) {
                $query->where('is_public', true)
                    ->where('discipline', TrainingDiscipline::General->value);
            })
            ->get()
            ->prepend($user)
            ->unique('id')
            ->values();

        $entries = $users
            ->map(function (User $candidate) use ($user) {
                return [
                    'user' => $candidate,
                    'streak' => $this->streakService->calculateStreakAsOf($candidate, $candidate->now()->subDay()),
                    'is_current_user' => $candidate->is($user),
                ];
            })
            ->values();

        $currentUserEntry = $entries->firstWhere('is_current_user', true);
        $otherEntries = $entries
            ->reject(fn (array $entry) => $entry['is_current_user'])
            ->sort(function (array $left, array $right): int {
                if ($left['streak'] !== $right['streak']) {
                    return $right['streak'] <=> $left['streak'];
                }

                return strcmp($left['user']->name, $right['user']->name);
            })
            ->values();

        return collect()
            ->when($currentUserEntry !== null, fn (Collection $collection) => $collection->push($currentUserEntry))
            ->concat($otherEntries)
            ->values();
    }

    private function buildSoccerDashboardData(User $user, Carbon $userNow): array
    {
        $programState = $this->trainingProgramService->buildDashboardState($user, $userNow, TrainingDiscipline::Soccer->value);
        $programs = $this->trainingCatalogService->getPrograms(TrainingDiscipline::Soccer->value);
        $assessmentDefinitions = $this->trainingCatalogService->getAssessments(TrainingDiscipline::Soccer->value);
        $assessmentResults = $user->assessmentResults()
            ->whereIn('assessment_slug', $assessmentDefinitions->pluck('slug'))
            ->orderByDesc('recorded_on')
            ->orderByDesc('id')
            ->get()
            ->groupBy('assessment_slug');

        $assessmentCards = $assessmentDefinitions
            ->map(function (array $assessment) use ($assessmentResults) {
                return $this->assessmentService->buildAssessmentView(
                    $assessment,
                    $assessmentResults->get($assessment['slug'], collect())
                );
            })
            ->values()
            ->all();

        $loadWindowStart = $userNow->copy()->subDays(6)->startOfDay()->utc();
        $loadWindowEnd = $userNow->copy()->endOfDay()->utc();
        $soccerSessions = $this->disciplineSessionQuery($user, TrainingDiscipline::Soccer->value)
            ->where(function (Builder $query) use ($loadWindowStart, $loadWindowEnd) {
                $query->whereBetween('completed_at', [$loadWindowStart, $loadWindowEnd])
                    ->orWhereBetween('started_at', [$loadWindowStart, $loadWindowEnd]);
            })
            ->with('sessionExercises')
            ->get();

        $soccerLoad = [
            'session_count' => $soccerSessions->count(),
            'total_seconds' => (int) $soccerSessions->sum(function (Session $session) {
                if ((int) $session->total_duration_seconds > 0) {
                    return (int) $session->total_duration_seconds;
                }

                return (int) $session->sessionExercises->sum(fn ($exercise) => (int) ($exercise->duration_seconds ?? 0));
            }),
            'window_label' => 'Last 7 days',
        ];

        return [
            ...$programState,
            'practice_library' => $this->trainingCatalogService->getTemplates(TrainingDiscipline::Soccer->value)->values()->all(),
            'assessment_cards' => $assessmentCards,
            'soccer_load' => $soccerLoad,
            'quick_start' => [
                'team_tracks' => $programs
                    ->filter(fn (array $program) => ! empty($program['team_practice_band']))
                    ->values()
                    ->all(),
                'conditioning_programs' => $programs
                    ->filter(fn (array $program) => empty($program['team_practice_band']))
                    ->values()
                    ->all(),
                'baseline_assessments' => collect($assessmentCards)
                    ->map(fn (array $card) => [
                        'slug' => $card['assessment']['slug'],
                        'name' => $card['assessment']['name'],
                        'description' => $card['assessment']['description'],
                        'has_result' => $card['latest'] !== null,
                    ])
                    ->values()
                    ->all(),
            ],
            'user_templates' => SessionTemplate::query()
                ->where('user_id', $user->id)
                ->where('discipline', TrainingDiscipline::Soccer->value)
                ->with(['practiceBlocks.exercise', 'exercises' => fn ($query) => $query->orderByPivot('order')])
                ->latest('updated_at')
                ->get(),
            'programs' => $programs->values()->all(),
        ];
    }

    private function disciplineSessionQuery(User $user, string $discipline): Builder
    {
        return Session::query()
            ->where('user_id', $user->id)
            ->where(function (Builder $query) use ($discipline) {
                $query->whereHas('template', fn (Builder $templateQuery) => $templateQuery->where('discipline', $discipline))
                    ->orWhereHas('sessionExercises.exercise', fn (Builder $exerciseQuery) => $exerciseQuery->where('discipline', $discipline));
            });
    }
}
