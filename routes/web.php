<?php

use App\Http\Controllers\PracticeRoutineController;
use App\Http\Controllers\ProductEventController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PracticeRoutineController::class, 'show']);

Route::post(
    '/analytics/events',
    ProductEventController::class
)
    ->middleware('throttle:product-analytics')
    ->name('analytics.events.store');