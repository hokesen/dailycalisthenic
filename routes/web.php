<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TemplateExerciseController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('home');

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
    Route::post('/templates/{template}/swap-exercise', [TemplateExerciseController::class, 'swap'])->name('templates.swap-exercise');
    Route::delete('/templates/{template}/remove-exercise', [TemplateExerciseController::class, 'remove'])->name('templates.remove-exercise');
    Route::post('/templates/{template}/add-exercise', [TemplateExerciseController::class, 'add'])->name('templates.add-exercise');
    Route::post('/templates/{template}/add-custom-exercise', [TemplateExerciseController::class, 'addCustom'])->name('templates.add-custom-exercise');
    Route::patch('/templates/{template}/update-exercise', [TemplateExerciseController::class, 'update'])->name('templates.update-exercise');
    Route::patch('/templates/{template}/move-exercise-up', [TemplateExerciseController::class, 'moveUp'])->name('templates.move-exercise-up');
    Route::patch('/templates/{template}/move-exercise-down', [TemplateExerciseController::class, 'moveDown'])->name('templates.move-exercise-down');
    Route::patch('/templates/{template}/update-name', [TemplateController::class, 'updateName'])->name('templates.update-name');
    Route::patch('/templates/{template}/toggle-visibility', [TemplateController::class, 'toggleVisibility'])->name('templates.toggle-visibility');
    Route::post('/templates/{template}/copy', [TemplateController::class, 'copy'])->name('templates.copy');
    Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
});

require __DIR__.'/auth.php';
