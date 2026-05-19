<?php

use App\Http\Controllers\PracticeRoutineController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PracticeRoutineController::class, 'show']);