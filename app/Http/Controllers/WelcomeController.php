<?php

namespace App\Http\Controllers;

use App\Models\PulsePreset;
use App\Services\PlaylistPlaybackBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class WelcomeController extends Controller
{
    public function index(
        Request $request,
        PlaylistPlaybackBuilder $playlistPlaybackBuilder,
    ) {
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

        $practiceMode = 'routine';
        $activePlaylistPayload = null;
        $practiceGroups = [];
        $practiceQueue = [];

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
            * Una URL no puede solicitar una rutina
            * y una playlist simultáneamente.
            */
            $requestedRoutineId = $request->query('routine');
            $requestedPlaylistId = $request->query('playlist');

            abort_if(
                $requestedRoutineId !== null
                && $requestedPlaylistId !== null,
                404
            );

            if ($requestedPlaylistId !== null) {
                /*
                * PLAYLIST MODE
                */
                $validatedPlaylistId = filter_var(
                    $requestedPlaylistId,
                    FILTER_VALIDATE_INT,
                    [
                        'options' => [
                            'min_range' => 1,
                        ],
                    ]
                );

                abort_if(
                    $validatedPlaylistId === false,
                    404
                );

                $playback = $playlistPlaybackBuilder->build(
                    user: $user,
                    playlistId: $validatedPlaylistId,
                );

                $practiceMode = 'playlist';

                $activePlaylistPayload =
                    $playback['active_playlist'];

                $practiceGroups =
                    $playback['groups'];

                $practiceQueue =
                    $playback['queue'];
            } else {
                /*
                * ROUTINE MODE
                */
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

                $routine->load([
                    'steps' => fn ($query) => $query
                        ->orderBy('position')
                        ->orderBy('id'),
                ]);

                $routinePayload = [
                    'id' => $routine->id,
                    'name' => $routine->name,
                    'position' => $routine->position,
                    'is_default' => (bool) $routine->is_default,

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
                            'alpha_tex' => $step->alpha_tex,
                            'position' => $step->position,
                            'origin' => $step->origin,
                        ])
                        ->values(),
                ];
            }

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
            $requestedRoutineId = $request->query('routine');

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
                        'time_signature_numerator' =>
                            $step->time_signature_numerator,

                        'time_signature_denominator' =>
                            $step->time_signature_denominator,
                        'mode' => $step->mode,
                        'duration_seconds' => $step->duration_seconds,
                        'alpha_tex' => $step->alpha_tex,
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

        $practiceContext = [
            'mode' => $practiceMode,

            'persistence' => $usesServerPersistence
                ? 'server'
                : 'local',

            'active_routine' => $routinePayload,
            'active_playlist' => $activePlaylistPayload,

            'groups' => $practiceGroups,

            'limits' => [
                'exercise_count' =>
                    $user?->exerciseLimit()
                    ?? User::TRIAL_EXERCISE_LIMIT,

                'exercise_duration_seconds' =>
                    $user?->exerciseDurationLimit()
                    ?? User::TRIAL_EXERCISE_DURATION_LIMIT,
            ],

            /*
            * Tanto Routine Mode como Playlist Mode entregan
            * una lista plana al reproductor.
            */
            'queue' => $practiceMode === 'playlist'
                ? $practiceQueue
                : ($routinePayload['steps'] ?? []),
        ];

        return view('welcome', [
            'practiceContext' => $practiceContext,

            'routine' => $routinePayload,
            'routines' => $routinesPayload,
            
            'activePlaylist' => $activePlaylistPayload,
            'practiceGroups' => $practiceGroups,
            'practiceQueue' => $practiceQueue,

            'usesServerPersistence' => $usesServerPersistence,
            'pulsePresets' => $pulsePresets,
        ]);
    }
}