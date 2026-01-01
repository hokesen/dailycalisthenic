<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $templates = \App\Models\SessionTemplate::query()
        ->availableFor(auth()->user())
        ->with(['exercises' => function ($query) {
            $query->with(['progression.easierExercise', 'progression.harderExercise'])
                ->orderByPivot('order');
        }])
        ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [auth()->id()])
        ->orderBy('name')
        ->get();

    $allExercises = \App\Models\Exercise::query()
        ->availableFor(auth()->user())
        ->orderBy('name')
        ->get();

    return view('dashboard', [
        'templates' => $templates,
        'allExercises' => $allExercises,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/go', [\App\Http\Controllers\GoController::class, 'index'])->name('go.index');
    Route::patch('/go/{session}/update', [\App\Http\Controllers\GoController::class, 'update'])->name('go.update');

    Route::post('/templates/{template}/swap-exercise', [\App\Http\Controllers\TemplateController::class, 'swapExercise'])->name('templates.swap-exercise');
    Route::delete('/templates/{template}/remove-exercise', [\App\Http\Controllers\TemplateController::class, 'removeExercise'])->name('templates.remove-exercise');
    Route::post('/templates/{template}/add-exercise', [\App\Http\Controllers\TemplateController::class, 'addExercise'])->name('templates.add-exercise');
    Route::post('/templates/{template}/add-custom-exercise', [\App\Http\Controllers\TemplateController::class, 'addCustomExercise'])->name('templates.add-custom-exercise');
    Route::patch('/templates/{template}/update-exercise', [\App\Http\Controllers\TemplateController::class, 'updateExercise'])->name('templates.update-exercise');
    Route::patch('/templates/{template}/update-name', [\App\Http\Controllers\TemplateController::class, 'updateName'])->name('templates.update-name');
});

require __DIR__.'/auth.php';
