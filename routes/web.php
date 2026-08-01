<?php

use App\Http\Controllers\PracticeRoutineStepController;
use App\Http\Controllers\ProductEventController;
use App\Http\Controllers\PulsePresetController;
use App\Http\Controllers\TrialController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])
    ->name('welcome');

Route::post('/analytics/events', ProductEventController::class)
    ->middleware('throttle:product-analytics')
    ->name('analytics.events.store');


// TODOS LOS USUARIOS AUTENTICADOS 
Route::view('/profile', 'profile.edit')
    ->middleware(['auth'])
    ->name('profile.edit');


// ROUTINES
Route::middleware(['auth', 'can:use-pro'])->group(function () {
    Route::post('/practice-routines/{practiceRoutine}/steps',
        [PracticeRoutineStepController::class, 'store'])
    ->name('practice-routine-steps.store');
    
    Route::patch(
        '/practice-routine-steps/{practiceRoutineStep}',
        [PracticeRoutineStepController::class, 'update']
    )->name('practice-routine-steps.update');
    
    Route::delete(
        '/practice-routine-steps/{practiceRoutineStep}',
        [PracticeRoutineStepController::class, 'destroy']
    )->name('practice-routine-steps.destroy');
});



// PULSE PATTERNS
Route::post('/pulse-presets', [PulsePresetController::class, 'store'])
    ->middleware('auth')
    ->name('pulse-presets.store');

Route::get('/pulse-presets', [PulsePresetController::class, 'index'])
    ->middleware('auth')
    ->name('pulse-presets.index');

Route::patch('/pulse-presets/{pulsePreset}', [PulsePresetController::class, 'update'])
    ->middleware('auth')
    ->name('pulse-presets.update');

Route::delete('/pulse-presets/{pulsePreset}', [PulsePresetController::class, 'destroy'])
    ->middleware('auth')
    ->name('pulse-presets.destroy');


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