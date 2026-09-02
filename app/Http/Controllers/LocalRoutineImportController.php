<?php

namespace App\Http\Controllers;

use App\Models\PracticeRoutine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocalRoutineImportController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $durationLimit =
            $user->exerciseDurationLimit();

        $validated = $request->validate([
            'steps' => [
                'required',
                'array',
                'min:1',
                'max:10',
            ],

            'steps.*.name' => [
                'required',
                'string',
                'max:255',
            ],

            'steps.*.bpm' => [
                'required',
                'integer',
                'min:30',
                'max:400',
            ],

            'steps.*.mode' => [
                'required',
                'in:timer,classic',
            ],

            'steps.*.duration_seconds' => [
                'nullable',
                'integer',
                'min:1',
                "max:{$durationLimit}",
            ],

            'steps.*.time_signature_numerator' => [
                'sometimes',
                'integer',
                'in:2,3,4',
            ],

            'steps.*.time_signature_denominator' => [
                'sometimes',
                'integer',
                'in:4',
            ],
        ]);

        $routine = DB::transaction(
            function () use (
                $user,
                $validated
            ): ?PracticeRoutine {
                /*
                 * Bloquear al usuario serializa las
                 * sincronizaciones incluso cuando todavía
                 * no tiene ninguna rutina.
                 */
                $lockedUser = $user
                    ->newQuery()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $routines = $lockedUser
                    ->practiceRoutines()
                    ->orderBy('position')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                /*
                 * Si ya existe una rutina vinculada con Free,
                 * siempre actualizamos únicamente esa.
                 */
                $freeLocalRoutine =
                    $routines->first(
                        fn (
                            PracticeRoutine $routine
                        ): bool =>
                            $routine->sync_source
                            === PracticeRoutine::SYNC_SOURCE_FREE_LOCAL
                    );

                /*
                 * Si no existe, esta es la primera
                 * importación: creamos una rutina nueva y
                 * dejamos intactas las rutinas normales.
                 */
                if (! $freeLocalRoutine) {
                    /*
                     * La primera importación necesita crear
                     * una rutina adicional.
                     *
                     * Si Trial ya alcanzó su límite, no
                     * modificamos ninguna rutina existente.
                     */
                    $routineLimit =
                        $lockedUser->routineLimit();

                    if (
                        $routineLimit !== null
                        && $routines->count()
                            >= $routineLimit
                    ) {
                        return null;
                    }

                    $freeLocalRoutine =
                        $lockedUser
                            ->practiceRoutines()
                            ->create([
                                'name' =>
                                    'Free Exercises',

                                'position' =>
                                    (
                                        (int) (
                                            $routines
                                                ->max('position')
                                            ?? -1
                                        )
                                    ) + 1,

                                'is_default' =>
                                    $routines->isEmpty(),

                                'sync_source' =>
                                    PracticeRoutine::SYNC_SOURCE_FREE_LOCAL,
                            ]);
                }

                /*
                 * La elección de conservar los ejercicios
                 * Free reemplaza solamente el contenido de
                 * free_local.
                 */
                $freeLocalRoutine
                    ->steps()
                    ->delete();

                $stepsToCreate =
                    collect($validated['steps'])
                        ->values()
                        ->map(
                            function (
                                array $step,
                                int $position
                            ): array {
                                $mode =
                                    $step['mode'];

                                return [
                                    'name' =>
                                        trim(
                                            $step['name']
                                        ),

                                    'bpm' =>
                                        (int) $step['bpm'],

                                    /*
                                     * Las rutinas Free
                                     * antiguas no contienen
                                     * compás. En ese caso
                                     * usamos 4/4.
                                     */
                                    'time_signature_numerator' =>
                                        (int) (
                                            $step[
                                                'time_signature_numerator'
                                            ]
                                            ?? 4
                                        ),

                                    'time_signature_denominator' =>
                                        (int) (
                                            $step[
                                                'time_signature_denominator'
                                            ]
                                            ?? 4
                                        ),

                                    'mode' =>
                                        $mode,

                                    'duration_seconds' =>
                                        $mode === 'timer'
                                            ? (int) $step[
                                                'duration_seconds'
                                            ]
                                            : null,

                                    'position' =>
                                        $position,

                                    'origin' =>
                                        'custom',
                                ];
                            }
                        )
                        ->all();

                $freeLocalRoutine
                    ->steps()
                    ->createMany(
                        $stepsToCreate
                    );

                $freeLocalRoutine->load([
                    'steps' =>
                        fn ($query) =>
                            $query
                                ->orderBy(
                                    'position'
                                )
                                ->orderBy('id'),
                ]);

                return $freeLocalRoutine;
            }
        );

        if ($routine === null) {
            return response()->json([
                'status' =>
                    'not_imported',

                'reason' =>
                    'trial_routine_limit',
            ], 409);
        }

        return response()->json([
            'status' =>
                'imported',

            'routine' => [
                'id' =>
                    $routine->id,

                'name' =>
                    $routine->name,

                'position' =>
                    (int) $routine->position,

                'is_default' =>
                    (bool) $routine->is_default,

                'sync_source' =>
                    $routine->sync_source,

                'steps' =>
                    $routine->steps
                        ->map(
                            fn ($step) => [
                                'id' =>
                                    $step->id,

                                'practice_routine_id' =>
                                    $step
                                        ->practice_routine_id,

                                'name' =>
                                    $step->name,

                                'bpm' =>
                                    $step->bpm,

                                'time_signature_numerator' =>
                                    $step
                                        ->time_signature_numerator,

                                'time_signature_denominator' =>
                                    $step
                                        ->time_signature_denominator,

                                'mode' =>
                                    $step->mode,

                                'duration_seconds' =>
                                    $step
                                        ->duration_seconds,

                                'position' =>
                                    $step->position,

                                'origin' =>
                                    $step->origin,
                            ]
                        )
                        ->values()
                        ->all(),
            ],
        ]);
    }
}