<?php

namespace App\Http\Controllers;

use App\Models\PracticeRoutine;
use Illuminate\Http\Request;

class PracticeRoutineController extends Controller
{
    public function show(PracticeRoutine $routine)
    {
        $routine->load('steps');

        return view('welcome', [
            'routine' => $routine
        ]);
    }
}
