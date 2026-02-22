<?php

use App\Http\Controllers\Api\HokesenIntegrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('integrations/hokesen/v1')
    ->middleware(['hokesen.assertion', 'throttle:30,1'])
    ->group(function () {
        Route::get('/quick-stats', [HokesenIntegrationController::class, 'quickStats']);
        Route::post('/journal-line', [HokesenIntegrationController::class, 'storeJournalLine']);
    });
