<?php

use App\Http\Controllers\PracticeRoutineController;
use App\Http\Controllers\ProductEventController;
use App\Http\Controllers\TrialController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PracticeRoutineController::class, 'show']);

Route::post(
    '/analytics/events',
    ProductEventController::class
)
    ->middleware('throttle:product-analytics')
    ->name('analytics.events.store');

Route::view('/profile', 'profile.edit')
    ->middleware(['auth'])
    ->name('profile.edit');

Route::post('/trial/activate', [TrialController::class, 'activate'])
    ->middleware(['auth', 'verified'])
    ->name('trial.activate');

Route::post('/trial/heartbeat', [TrialController::class, 'heartbeat'])
    ->middleware(['auth', 'verified'])
    ->name('trial.heartbeat');

Route::post('/trial/resume', [TrialController::class, 'resume'])
    ->middleware(['auth', 'verified'])
    ->name('trial.resume');