<?php

namespace App\Http\Controllers;

use App\Models\PulsePreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WelcomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $routine = DB::transaction(function () use ($user) {
            $routine = $user->practiceRoutines()
                ->firstOrCreate(
                    [
                        'is_default' => true,
                    ],
                    [
                        'name' => 'My Exercises',
                        'position' => 0,
                    ]
                );

            if ($routine->steps()->doesntExist()) {
                $routine->steps()->createMany([
                    [
                        'name' => 'Alternate Picking',
                        'bpm' => 100,
                        'mode' => 'timer',
                        'duration_seconds' => 5,
                        'position' => 0,
                        'origin' => 'preset',
                    ],
                    [
                        'name' => 'Legato',
                        'bpm' => 80,
                        'mode' => 'timer',
                        'duration_seconds' => 5,
                        'position' => 1,
                        'origin' => 'preset',
                    ],
                    [
                        'name' => 'Sweep Picking',
                        'bpm' => 90,
                        'mode' => 'timer',
                        'duration_seconds' => 60,
                        'position' => 2,
                        'origin' => 'preset',
                    ],
                ]);
            }

            return $routine->load('steps');
        });

        $pulsePresets = PulsePreset::query()
            ->where('user_id', '=', null)
            ->orderBy('id', 'asc')
            ->get();

        $routinePayload = [
            'id' => $routine->id,
            'name' => $routine->name,
            'position' => $routine->position,
            'is_default' => $routine->is_default,

            'steps' => $routine->steps
                ->map(fn ($step) => [
                    'id' => $step->id,
                    'practice_routine_id' =>
                        $step->practice_routine_id,

                    'name' => $step->name,
                    'bpm' => $step->bpm,
                    'mode' => $step->mode,

                    'duration_seconds' =>
                        $step->duration_seconds,

                    'position' => $step->position,
                    'origin' => $step->origin,
                ])
                ->values(),
        ];

        return view('welcome', [
            'routine' => $routinePayload,
            'pulsePresets' => $pulsePresets,
        ]);
    }
}