<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\SessionTemplate;
use App\Models\User;
use App\Repositories\ExerciseRepository;
use App\Services\StreakService;
use App\Services\UserActivityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ExerciseRepository $exerciseRepository,
        private readonly StreakService $streakService,
        private readonly UserActivityService $activityService
    ) {}

    public function index(Request $request): View
    {
        if (! auth()->check()) {
            if ($request->has('template')) {
                return redirect()->guest(route('login'));
            }

            return view('welcome');
        }

        $user = auth()->user();

        $allExercises = $this->exerciseRepository->getAvailableForUser($user);

        $userNow = $user->now();
        $startDate = $userNow->copy()->subDays(6)->startOfDay();
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

        if ($authUserTemplates->isNotEmpty()) {
            $userCarouselData->push([
                'user' => $user,
                'templates' => $authUserTemplates,
                'currentStreak' => $this->streakService->calculateStreak($user),
                'weeklyBreakdown' => $this->activityService->getWeeklyExerciseBreakdown($user, 7),
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
                    'weeklyBreakdown' => $this->activityService->getWeeklyExerciseBreakdown($otherUser, 7),
                    'topTemplateId' => $publicTemplates->first()->id,
                ]);
            }
        }

        $progressionGanttData = $user->getProgressionGanttData(7);

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

        return view('dashboard', [
            'userCarouselData' => $userCarouselData,
            'allExercises' => $allExercises,
            'authUserStreak' => $this->streakService->calculateStreak($user),
            'potentialStreak' => $this->streakService->calculatePotentialStreak($user),
            'progressionGanttData' => $progressionGanttData,
            'initialTemplateIndex' => $initialTemplateIndex,
            'selectedTemplateId' => $selectedTemplateId,
            'hasPracticedToday' => $hasPracticedToday,
        ]);
    }
}
