<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\Session;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Repositories\ExerciseRepository;
use App\Services\StarterTemplateService;
use App\Services\StreakService;
use App\Services\UserActivityService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ExerciseRepository $exerciseRepository,
        private readonly StreakService $streakService,
        private readonly UserActivityService $activityService,
        private readonly StarterTemplateService $starterTemplateService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (! auth()->check()) {
            if ($request->has('template')) {
                return redirect()->guest(route('login'));
            }

            return view('welcome');
        }

        $this->starterTemplateService->ensureStarterTemplates();

        $user = auth()->user();

        // Get exercises from user's templates and recent sessions
        // This ensures we show the actual exercises being used, not just defaults
        $templateExercises = \App\Models\Exercise::query()
            ->whereHas('sessionTemplates', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        $sessionExercises = \App\Models\Exercise::query()
            ->whereHas('sessions', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        // Merge and get unique exercises the user has actually used
        $userUsedExercises = $templateExercises->merge($sessionExercises)->unique('id');

        // Also include available exercises for selection
        $availableExercises = $this->exerciseRepository->getAvailableForUser($user);

        // Combine both lists, preferring actual used exercises
        // Filter out duplicates by both ID and name to avoid showing default and DB versions of same exercise
        $allExercises = $userUsedExercises->merge(
            $availableExercises->filter(fn ($e) =>
                ! $userUsedExercises->contains('id', $e->id) &&
                ! $userUsedExercises->contains('name', $e->name)
            )
        )->sortBy('name');

        // Get days filter from request (default: 7)
        $days = (int) $request->query('days', 7);
        // Validate days parameter
        $days = in_array($days, [7, 14, 30, 90]) ? $days : 7;

        $userNow = $user->now();
        $userTimezone = $user->timezone ?? 'America/Los_Angeles';
        $startDate = $userNow->copy()->subDays($days - 1)->startOfDay();
        $endDate = $userNow->copy()->endOfDay();
        $startDateUtc = $startDate->copy()->timezone('UTC');
        $endDateUtc = $endDate->copy()->timezone('UTC');

        $userCarouselData = collect();

        $authUserTemplates = SessionTemplate::query()
            ->where('user_id', $user->id)
            ->with([
                'user',
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
            ->with([
                'user',
                'exercises' => function ($query) {
                    $query->with(['progression.easierExercise', 'progression.harderExercise'])
                        ->orderByPivot('order');
                },
            ])
            ->get();

        if ($authUserTemplates->isNotEmpty()) {
            $userCarouselData->push([
                'user' => $user,
                'templates' => $authUserTemplates,
                'currentStreak' => $this->streakService->calculateStreak($user),
                'weeklyBreakdown' => $this->activityService->getWeeklyExerciseBreakdown($user, $days),
                'topTemplateId' => $authUserTemplates->first()->id ?? null,
            ]);
        }

        $otherUsers = User::query()
            ->where('id', '!=', $user->id)
            ->with('activeGoal')
            ->whereHas('sessionTemplates', function ($query) {
                $query->where('is_public', true);
            })
            ->get();

        foreach ($otherUsers as $otherUser) {
            $publicTemplates = SessionTemplate::query()
                ->where('user_id', $otherUser->id)
                ->where('is_public', true)
                ->with([
                    'user',
                    'exercises' => function ($query) {
                        $query->with(['progression.easierExercise', 'progression.harderExercise'])
                            ->orderByPivot('order');
                    },
                ])
                ->withSum(['sessions' => function ($query) use ($otherUser, $startDateUtc, $endDateUtc) {
                    $query->where('user_id', $otherUser->id)
                        ->completed()
                        ->whereBetween('completed_at', [$startDateUtc, $endDateUtc]);
                }], 'total_duration_seconds')
                ->orderByDesc('sessions_sum_total_duration_seconds')
                ->get();

            if ($publicTemplates->isNotEmpty()) {
                $userCarouselData->push([
                    'user' => $otherUser,
                    'templates' => $publicTemplates,
                    'currentStreak' => $this->streakService->calculateStreak($otherUser),
                    'weeklyBreakdown' => $this->activityService->getWeeklyExerciseBreakdown($otherUser, $days),
                    'topTemplateId' => $publicTemplates->first()->id,
                ]);
            }
        }

        $progressionGanttData = $user->getProgressionGanttData($days);

        $todayStartUtc = $userNow->copy()->startOfDay()->timezone('UTC');
        $todayEndUtc = $userNow->copy()->endOfDay()->timezone('UTC');
        $hasPracticedToday = Session::query()
            ->where('user_id', $user->id)
            ->completed()
            ->whereBetween('completed_at', [$todayStartUtc, $todayEndUtc])
            ->exists();

        $selectedTemplateId = $request->query('template');
        $initialTemplateIndex = 0;

        if ($selectedTemplateId && $authUserTemplates->isNotEmpty()) {
            $index = $authUserTemplates->search(fn ($t) => $t->id == $selectedTemplateId);
            if ($index !== false) {
                $initialTemplateIndex = $index;
            }
        }

        $timelineFeed = $this->getTimelineFeed($user, $days);
        $userTemplates = $authUserTemplates;

        $todayEntry = JournalEntry::query()
            ->where('user_id', $user->id)
            ->forDate($userNow)
            ->with('journalExercises')
            ->first();

        $currentUserGoal = $user->goals()->active()->first();

        return view('dashboard', [
            'userCarouselData' => $userCarouselData,
            'allExercises' => $allExercises,
            'authUserStreak' => $this->streakService->calculateStreak($user),
            'potentialStreak' => $this->streakService->calculatePotentialStreak($user),
            'progressionGanttData' => $progressionGanttData,
            'initialTemplateIndex' => $initialTemplateIndex,
            'selectedTemplateId' => $selectedTemplateId,
            'hasPracticedToday' => $hasPracticedToday,
            'timelineFeed' => $timelineFeed,
            'userTimezone' => $userTimezone,
            'userNow' => $userNow,
            'userTemplates' => $userTemplates,
            'todayEntry' => $todayEntry,
            'days' => $days,
            'currentUserGoal' => $currentUserGoal,
            'systemTemplates' => $systemTemplates,
        ]);
    }

    private function getTimelineFeed(User $user, int $days): Collection
    {
        $startDate = $user->now()->subDays($days - 1)->startOfDay();
        $endDate = $user->now()->endOfDay();

        $timezone = $user->timezone ?? 'America/Los_Angeles';

        $sessions = Session::query()
            ->where('user_id', $user->id)
            ->completed()
            ->whereBetween('completed_at', [$startDate->copy()->utc(), $endDate->copy()->utc()])
            ->with(['sessionExercises.exercise', 'template'])
            ->get()
            ->map(fn ($s) => [
                'type' => 'session',
                'date' => $s->completed_at->setTimezone($timezone),
                'data' => $s,
            ]);

        $journals = JournalEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->with('journalExercises')
            ->get()
            ->map(fn ($j) => [
                'type' => 'journal',
                'date' => Carbon::parse($j->entry_date->format('Y-m-d'), $timezone)->endOfDay(),
                'data' => $j,
            ]);

        return $sessions->toBase()->merge($journals->toBase())
            ->sortByDesc('date')
            ->groupBy(fn ($item) => $item['date']->format('Y-m-d'));
    }
}
