<?php

use App\Http\Controllers\GoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TemplateController;
use App\Repositories\ExerciseRepository;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // If not logged in, show the marketing page
    if (! auth()->check()) {
        // Redirect to login if they have a template parameter (deep link)
        if (request()->has('template')) {
            return redirect()->guest(route('login'));
        }

        return view('welcome');
    }

    // Otherwise show the dashboard
    $user = auth()->user();

    $allExercises = app(ExerciseRepository::class)->getAvailableForUser($user);

    // Get date range for the past week
    $userNow = $user->now();
    $startDate = $userNow->copy()->subDays(6)->startOfDay();
    $endDate = $userNow->copy()->endOfDay();
    $startDateUtc = $startDate->copy()->timezone('UTC');
    $endDateUtc = $endDate->copy()->timezone('UTC');

    $userCarouselData = collect();

    // Get ALL templates for the current user (not just ones used recently)
    $authUserTemplates = \App\Models\SessionTemplate::query()
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
            'currentStreak' => $user->getCurrentStreak(),
            'weeklyBreakdown' => $user->getWeeklyExerciseBreakdown(7),
            'topTemplateId' => $authUserTemplates->first()->id ?? null,
        ]);
    }

    // Get all other users who have public templates
    $otherUsers = \App\Models\User::query()
        ->where('id', '!=', $user->id)
        ->with('activeGoal')
        ->whereHas('sessionTemplates', function ($query) {
            $query->where('is_public', true);
        })
        ->get();

    foreach ($otherUsers as $otherUser) {
        // Get all public templates from this user
        $publicTemplates = \App\Models\SessionTemplate::query()
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
                'currentStreak' => $otherUser->getCurrentStreak(),
                'weeklyBreakdown' => $otherUser->getWeeklyExerciseBreakdown(7),
                'topTemplateId' => $publicTemplates->first()->id,
            ]);
        }
    }

    // Get progression gantt data for the current user
    $progressionGanttData = $user->getProgressionGanttData(7);

    // Check if user has practiced today
    $todayStartUtc = $userNow->copy()->startOfDay()->timezone('UTC');
    $todayEndUtc = $userNow->copy()->endOfDay()->timezone('UTC');
    $hasPracticedToday = \App\Models\Session::query()
        ->where('user_id', $user->id)
        ->completed()
        ->whereBetween('completed_at', [$todayStartUtc, $todayEndUtc])
        ->exists();

    // Determine initial template index from query parameter
    $selectedTemplateId = request()->query('template');
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
        'authUserStreak' => $user->getCurrentStreak(),
        'potentialStreak' => $user->getPotentialStreak(),
        'progressionGanttData' => $progressionGanttData,
        'initialTemplateIndex' => $initialTemplateIndex,
        'selectedTemplateId' => $selectedTemplateId,
        'hasPracticedToday' => $hasPracticedToday,
    ]);
})->name('home');

// Redirect /dashboard to / for backwards compatibility
Route::get('/dashboard', function () {
    return redirect()->route('home');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/go', [GoController::class, 'index'])->name('go.index');
    Route::patch('/go/{session}/update', [GoController::class, 'update'])->name('go.update');

    Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}/card', [TemplateController::class, 'card'])->name('templates.card');
    Route::post('/templates/{template}/swap-exercise', [TemplateController::class, 'swapExercise'])->name('templates.swap-exercise');
    Route::delete('/templates/{template}/remove-exercise', [TemplateController::class, 'removeExercise'])->name('templates.remove-exercise');
    Route::post('/templates/{template}/add-exercise', [TemplateController::class, 'addExercise'])->name('templates.add-exercise');
    Route::post('/templates/{template}/add-custom-exercise', [TemplateController::class, 'addCustomExercise'])->name('templates.add-custom-exercise');
    Route::patch('/templates/{template}/update-exercise', [TemplateController::class, 'updateExercise'])->name('templates.update-exercise');
    Route::patch('/templates/{template}/move-exercise-up', [TemplateController::class, 'moveExerciseUp'])->name('templates.move-exercise-up');
    Route::patch('/templates/{template}/move-exercise-down', [TemplateController::class, 'moveExerciseDown'])->name('templates.move-exercise-down');
    Route::patch('/templates/{template}/update-name', [TemplateController::class, 'updateName'])->name('templates.update-name');
    Route::patch('/templates/{template}/toggle-visibility', [TemplateController::class, 'toggleVisibility'])->name('templates.toggle-visibility');
    Route::post('/templates/{template}/copy', [TemplateController::class, 'copy'])->name('templates.copy');
    Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
});

require __DIR__.'/auth.php';
