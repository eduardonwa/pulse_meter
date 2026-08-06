<?php

use App\Http\Controllers\Billing\LifetimeProCheckoutController;
use App\Http\Controllers\Billing\MonthlyProCheckoutController;
use App\Http\Controllers\BillingPageController;
use App\Http\Controllers\LocalRoutineImportController;
use App\Http\Controllers\PracticeRoutineController;
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
Route::view('/account/profile', 'profile.edit')
    ->middleware('auth')
    ->name('profile.edit');

Route::get('/account/billing', BillingPageController::class)
    ->middleware(['auth', 'verified'])
    ->name('billing.index');

/*
 * Enlaces antiguos conservados temporalmente.
 */
Route::redirect('/profile', '/account/profile')
    ->middleware('auth');

Route::redirect('/billing', '/account/billing')
    ->middleware(['auth', 'verified']);

// CHECKOUT
Route::post(
    '/billing/pro/monthly/checkout',
    MonthlyProCheckoutController::class
)
    ->middleware('auth')
    ->name('billing.pro.monthly.checkout');

Route::post(
    '/billing/pro/lifetime/checkout',
    LifetimeProCheckoutController::class
)
    ->middleware('auth')
    ->name('billing.pro.lifetime.checkout');

// ROUTINES
Route::middleware(['auth', 'can:use-pro'])->group(function () {
    // ROUTINES
    Route::post(
        '/practice-routines/import-local',
        LocalRoutineImportController::class
    )->name('practice-routines.import-local');

    Route::post(
        '/practice-routines',
        [PracticeRoutineController::class, 'store']
    )->name('practice-routines.store');

    Route::patch(
        '/practice-routines/{practiceRoutine}',
        [PracticeRoutineController::class, 'update']
    )->name('practice-routines.update');

    Route::delete(
        '/practice-routines/{practiceRoutine}',
        [PracticeRoutineController::class, 'destroy']
    )->name('practice-routines.destroy');

    Route::patch(
        '/practice-routines/{practiceRoutine}/move',
        [PracticeRoutineController::class, 'move']
    )->name('practice-routines.move');


    // EXERCISES
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


    // PULSE PATTERNS
    Route::post('/pulse-presets',
        [PulsePresetController::class, 'store'])
    ->name('pulse-presets.store');

    Route::get('/pulse-presets',
        [PulsePresetController::class, 'index'])
    ->name('pulse-presets.index');

    Route::patch('/pulse-presets/{pulsePreset}',
        [PulsePresetController::class, 'update'])
    ->name('pulse-presets.update');

    Route::delete('/pulse-presets/{pulsePreset}',
        [PulsePresetController::class, 'destroy'])
    ->name('pulse-presets.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // TRIAL MODE
    Route::post('/trial/activate',
        [TrialController::class, 'activate'])
    ->name('trial.activate');

    Route::post('/trial/heartbeat',
        [TrialController::class, 'heartbeat'])
    ->name('trial.heartbeat');

    Route::post('/trial/resume',
        [TrialController::class, 'resume'])
    ->name('trial.resume');

    Route::post('/trial/pause',
        [TrialController::class, 'pause'])
    ->name('trial.pause');
});