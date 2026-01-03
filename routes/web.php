<?php

use App\Http\Controllers\GoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TemplateController;
use App\Models\Exercise;
use App\Models\SessionTemplate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $allTemplates = SessionTemplate::query()
        ->with([
            'user',
            'exercises' => function ($query) {
                $query->with(['progression.easierExercise', 'progression.harderExercise'])
                    ->orderByPivot('order');
            },
        ])
        ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [auth()->id()])
        ->orderBy('name')
        ->get();

    $allExercises = Exercise::query()
        ->availableFor(auth()->user())
        ->orderBy('name')
        ->get();

    $user = auth()->user();
    $weeklyBreakdown = $user->getWeeklyExerciseBreakdown(7);
    $progressionSummary = $user->getWeeklyProgressionSummary(7);
    $currentStreak = $user->getCurrentStreak();

    return view('dashboard', [
        'allTemplates' => $allTemplates,
        'allExercises' => $allExercises,
        'weeklyBreakdown' => $weeklyBreakdown,
        'progressionSummary' => $progressionSummary,
        'currentStreak' => $currentStreak,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

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
    Route::patch('/templates/{template}/update-name', [TemplateController::class, 'updateName'])->name('templates.update-name');
    Route::post('/templates/{template}/copy', [TemplateController::class, 'copy'])->name('templates.copy');
    Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
});

require __DIR__.'/auth.php';
