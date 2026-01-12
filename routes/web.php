<?php

use App\Http\Controllers\GoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TemplateController;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $allExercises = Exercise::query()
        ->availableFor(auth()->user())
        ->orderBy('name')
        ->get();

    // Get date range for the past week
    $userNow = auth()->user()->now();
    $startDate = $userNow->copy()->subDays(6)->startOfDay();
    $endDate = $userNow->copy()->endOfDay();
    $startDateUtc = $startDate->copy()->timezone('UTC');
    $endDateUtc = $endDate->copy()->timezone('UTC');

    $userCarouselData = collect();

    // Get ALL templates for the current user (not just ones used recently)
    $authUserTemplates = \App\Models\SessionTemplate::query()
        ->where('user_id', auth()->id())
        ->with([
            'user',
            'exercises' => function ($query) {
                $query->with(['progression.easierExercise', 'progression.harderExercise'])
                    ->orderByPivot('order');
            },
        ])
        ->withSum(['sessions' => function ($query) use ($startDateUtc, $endDateUtc) {
            $query->where('user_id', auth()->id())
                ->completed()
                ->whereBetween('completed_at', [$startDateUtc, $endDateUtc]);
        }], 'total_duration_seconds')
        ->orderByDesc('sessions_sum_total_duration_seconds')
        ->get();

    if ($authUserTemplates->isNotEmpty()) {
        $userCarouselData->push([
            'user' => auth()->user(),
            'templates' => $authUserTemplates,
            'currentStreak' => auth()->user()->getCurrentStreak(),
            'weeklyBreakdown' => auth()->user()->getWeeklyExerciseBreakdown(7),
            'topTemplateId' => $authUserTemplates->first()->id ?? null,
        ]);
    }

    // Get all other users who have public templates
    $otherUsers = \App\Models\User::query()
        ->where('id', '!=', auth()->id())
        ->with('activeGoal')
        ->whereHas('sessionTemplates', function ($query) {
            $query->where('is_public', true);
        })
        ->get();

    foreach ($otherUsers as $user) {
        // Get all public templates from this user
        $publicTemplates = \App\Models\SessionTemplate::query()
            ->where('user_id', $user->id)
            ->where('is_public', true)
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

        if ($publicTemplates->isNotEmpty()) {
            $userCarouselData->push([
                'user' => $user,
                'templates' => $publicTemplates,
                'currentStreak' => $user->getCurrentStreak(),
                'weeklyBreakdown' => $user->getWeeklyExerciseBreakdown(7),
                'topTemplateId' => $publicTemplates->first()->id,
            ]);
        }
    }

    return view('dashboard', [
        'userCarouselData' => $userCarouselData,
        'allExercises' => $allExercises,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/activity', function (Request $request) {
    $user = auth()->user();
    $range = $request->input('range', 'week');

    // Validate range input
    if (! in_array($range, ['week', 'month'])) {
        $range = 'week';
    }

    $days = $range === 'month' ? 30 : 7;

    $progressionSummary = $user->getWeeklyProgressionSummary($days);
    $standaloneExercises = $user->getWeeklyStandaloneExercises($days);

    return view('activity', [
        'progressionSummary' => $progressionSummary,
        'standaloneExercises' => $standaloneExercises,
        'selectedRange' => $range,
    ]);
})->middleware(['auth', 'verified'])->name('activity');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/go', [GoController::class, 'index'])->name('go.index');
    Route::patch('/go/{session}/update', [GoController::class, 'update'])->name('go.update');

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
