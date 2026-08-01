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

        /*
         * Pueden usar persistencia en servidor:
         *
         * - usuarios Pro;
         * - usuarios con Trial Mode activo.
         *
         * Invitados y Free sin trial reciben $routine = null
         * y continúan usando localStorage.
         */
        $usesServerPersistence = $user?->hasProAccess() ?? false;

        /*
         * Valores predeterminados para el flujo sin
         * persistencia en servidor.
         */
        $routinePayload = null;
        $routinesPayload = collect();

        if ($usesServerPersistence) {
            /*
             * 1. Garantiza que exista una rutina predeterminada.
             * 2. Garantiza que esa rutina tenga ejercicios.
             */
            $defaultRoutine = DB::transaction(
                function () use ($user) {
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

                    return $routine;
                }
            );

            /*
             * Resolver la rutina activa.
             *
             * Sin ?routine=:
             * carga la predeterminada.
             *
             * Con ?routine=4:
             * carga la rutina 4 únicamente si pertenece
             * al usuario autenticado.
             */
            $requestedRoutineId =
                $request->query('routine');

            if ($requestedRoutineId === null) {
                $routine = $defaultRoutine;
            } else {
                $validatedRoutineId = filter_var(
                    $requestedRoutineId,
                    FILTER_VALIDATE_INT,
                    [
                        'options' => [
                            'min_range' => 1,
                        ],
                    ]
                );

                abort_if(
                    $validatedRoutineId === false,
                    404
                );

                $routine = $user->practiceRoutines()
                    ->whereKey($validatedRoutineId)
                    ->firstOrFail();
            }

            /*
             * Solo la rutina activa carga sus steps completos.
             */
            $routine->load([
                'steps' => fn ($query) => $query
                    ->orderBy('position')
                    ->orderBy('id'),
            ]);

            /*
             * Payload completo de la rutina activa.
             */
            $routinePayload = [
                'id' => $routine->id,
                'name' => $routine->name,
                'position' => $routine->position,
                'is_default' => (bool) $routine->is_default,

                'steps' => $routine->steps
                    ->map(fn ($step) => [
                        'id' => $step->id,

                        'practice_routine_id' => $step->practice_routine_id,
                        'name' => $step->name,
                        'bpm' => $step->bpm,
                        'mode' => $step->mode,
                        'duration_seconds' => $step->duration_seconds,
                        'position' => $step->position,
                        'origin' => $step->origin,
                    ])
                    ->values(),
            ];

            /*
             * Lista resumida para el selector de rutinas.
             *
             * No cargamos todos los steps de todas las rutinas.
             */
            $routinesPayload = $user->practiceRoutines()
                ->select([
                    'id',
                    'name',
                    'position',
                    'is_default',
                ])
                ->withCount('steps')
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->map(fn ($routineOption) => [
                    'id' => $routineOption->id,
                    'name' => $routineOption->name,
                    'position' =>
                        $routineOption->position,

                    'is_default' =>
                        (bool) $routineOption->is_default,

                    'steps_count' =>
                        $routineOption->steps_count,
                ])
                ->values();
        }

        /*
         * Los presets están disponibles para todos.
         */
        $pulsePresets = PulsePreset::query()
            ->where('user_id', '=', null)
            ->orderBy('id', 'asc')
            ->get();

        return view('welcome', [
            'routine' => $routinePayload,
            'routines' => $routinesPayload,
            'usesServerPersistence' => $usesServerPersistence,
            'pulsePresets' => $pulsePresets,
        ]);
    }
}