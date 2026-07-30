<?php

use App\Http\Controllers\PracticeRoutineController;
use App\Http\Controllers\ProductEventController;
use App\Http\Controllers\PulsePatternController;
use App\Http\Controllers\TrialController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PracticeRoutineController::class, 'show']);

Route::post('/analytics/events', ProductEventController::class)
    ->middleware('throttle:product-analytics')
    ->name('analytics.events.store');

Route::view('/profile', 'profile.edit')
    ->middleware(['auth'])
    ->name('profile.edit');


// PULSE PATTERNS
Route::post('/pulse-patterns', [PulsePatternController::class, 'store'])
    ->middleware('auth')
    ->name('pulse-patterns.store');

Route::get('/pulse-patterns', [PulsePatternController::class, 'index'])
    ->middleware('auth')
    ->name('pulse-patterns.index');

Route::patch('/pulse-patterns/{pulsePattern}', [PulsePatternController::class, 'update'])
    ->middleware('auth')
    ->name('pulse-patterns.update');

Route::delete('/pulse-patterns/{pulsePattern}', [PulsePatternController::class, 'destroy'])
    ->middleware('auth')
    ->name('pulse-patterns.destroy');


// TRIAL MODE
Route::post('/trial/activate', [TrialController::class, 'activate'])
    ->middleware(['auth', 'verified'])
    ->name('trial.activate');

Route::post('/trial/heartbeat', [TrialController::class, 'heartbeat'])
    ->middleware(['auth', 'verified'])
    ->name('trial.heartbeat');

Route::post('/trial/resume', [TrialController::class, 'resume'])
    ->middleware(['auth', 'verified'])
    ->name('trial.resume');

Route::post('/trial/pause', [TrialController::class, 'pause'])
    ->middleware(['auth', 'verified'])
    ->name('trial.pause');