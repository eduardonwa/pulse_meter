<?php

namespace App\Http\Controllers;

use App\Models\PracticeRoutine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocalRoutineImportController extends Controller
{
    public function __invoke(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        $durationLimit = $user->exerciseDurationLimit();

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
        ]);

        $routine = DB::transaction(
            function () use (
                $user,
                $validated,
            ): ?PracticeRoutine {
                /*
                 * Bloquear al usuario serializa las sincronizaciones
                 * incluso cuando todavía no tiene ninguna rutina.
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
                $freeLocalRoutine = $routines->first(
                    fn (PracticeRoutine $routine): bool =>
                        $routine->sync_source
                        === PracticeRoutine::SYNC_SOURCE_FREE_LOCAL
                );

                if (! $freeLocalRoutine) {
                    /*
                     * Una rutina sin sync_source todavía no tiene
                     * una procedencia especial asignada.
                     */
                    $unlinkedRoutines = $routines
                        ->filter(
                            fn (PracticeRoutine $routine): bool =>
                                $routine->sync_source === null
                        )
                        ->values();

                    /*
                     * Si hay varias candidatas, no adivinamos cuál
                     * debe ser reemplazada por los ejercicios Free.
                     */
                    if ($unlinkedRoutines->count() > 1) {
                        return null;
                    }

                    /*
                     * Si solamente existe una rutina antigua,
                     * la adoptamos como la rutina Free.
                     */
                    if ($unlinkedRoutines->count() === 1) {
                        $freeLocalRoutine =
                            $unlinkedRoutines->first();

                        $freeLocalRoutine->sync_source =
                            PracticeRoutine::SYNC_SOURCE_FREE_LOCAL;

                        $freeLocalRoutine->save();
                    } else {
                        /*
                         * Este caso cubre una cuenta sin rutinas.
                         * El flujo normal suele crear una antes,
                         * pero el endpoint queda completo por sí mismo.
                         */
                        $lastPosition =
                            $routines->max('position');

                        $freeLocalRoutine = $lockedUser
                            ->practiceRoutines()
                            ->create([
                                'name' => 'Free Exercises',

                                'position' =>
                                    $lastPosition === null
                                        ? 0
                                        : ((int) $lastPosition) + 1,

                                'is_default' =>
                                    $routines->isEmpty(),

                                'sync_source' =>
                                    PracticeRoutine::SYNC_SOURCE_FREE_LOCAL,
                            ]);
                    }
                }

                /*
                 * La elección "Use my Free exercises" es explícita:
                 * reemplazamos solo los ejercicios de free_local.
                 */
                $freeLocalRoutine
                    ->steps()
                    ->delete();

                $stepsToCreate = collect(
                    $validated['steps']
                )
                    ->values()
                    ->map(
                        function (
                            array $step,
                            int $position,
                        ): array {
                            $mode = $step['mode'];

                            return [
                                'name' =>
                                    trim($step['name']),

                                'bpm' =>
                                    (int) $step['bpm'],

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
                    ->createMany($stepsToCreate);

                $freeLocalRoutine->load([
                    'steps' => fn ($query) => $query
                        ->orderBy('position')
                        ->orderBy('id'),
                ]);

                return $freeLocalRoutine;
            }
        );

        if ($routine === null) {
            return response()->json([
                'status' => 'not_imported',

                'reason' =>
                    'free_local_routine_ambiguous',
            ], 409);
        }

        return response()->json([
            'status' => 'imported',

            'routine' => [
                'id' => $routine->id,
                'name' => $routine->name,

                'position' =>
                    (int) $routine->position,

                'is_default' =>
                    (bool) $routine->is_default,

                'sync_source' =>
                    $routine->sync_source,

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

                        'position' =>
                            $step->position,

                        'origin' =>
                            $step->origin,
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }
}