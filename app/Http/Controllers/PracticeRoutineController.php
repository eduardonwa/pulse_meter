<?php

namespace App\Http\Controllers;

use App\Models\PracticeRoutine;
use App\Models\PulsePreset;

class PracticeRoutineController extends Controller
{
    public function show(PracticeRoutine $routine)
    {
        $routine->load('steps');

        $pulsePresets = PulsePreset::query()
            ->where('user_id', '=', null)
            ->orderBy('id', 'asc')
            ->get();

        return view('welcome', [
            'routine' => $routine,
            'pulsePresets' => $pulsePresets
        ]);
    }
}