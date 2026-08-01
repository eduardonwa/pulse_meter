<?php

namespace App\Http\Controllers;

use App\Models\PracticeRoutine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PracticeRoutineController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()
            ->practiceRoutines()
            ->with('steps')
            ->get();
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $routine = DB::transaction(function () use ($user) {
            /*
             * La nueva rutina se coloca después
             * de las rutinas existentes.
             */
            $lastPosition = $user->practiceRoutines()
                ->max('position');

            $nextPosition =
                $lastPosition === null
                    ? 0
                    : $lastPosition + 1;

            /*
             * En el flujo normal ya existe la default,
             * pero esto mantiene el método seguro si por
             * alguna razón se llama sin rutinas previas.
             */
            $isFirstRoutine = $user->practiceRoutines()
                ->doesntExist();

            $routine = $user->practiceRoutines()->create([
                'name' => 'Routine ' . ($nextPosition + 1),
                'position' => $nextPosition,
                'is_default' => $isFirstRoutine,
            ]);

            /*
             * Toda rutina comienza con al menos
             * un ejercicio editable.
             */
            $routine->steps()->create([
                'name' => 'New Exercise',
                'bpm' => 100,
                'mode' => 'timer',
                'duration_seconds' => 60,
                'position' => 0,
                'origin' => 'custom',
            ]);

            return $routine;
        });

        return redirect()->route('welcome', [
            'routine' => $routine->id,
        ]);
    }

    public function update(
        Request $request,
        PracticeRoutine $practiceRoutine
    ) {
        /*
        * Una rutina ajena se comporta como si no existiera.
        */
        abort_unless(
            (int) $practiceRoutine->user_id
                === (int) $request->user()->id,
            404
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:80',
            ],
        ]);

        /*
        * Asignación directa para no depender de $fillable.
        */
        $practiceRoutine->name = $validated['name'];
        $practiceRoutine->save();

        /*
        * Regresa a la misma rutina activa que estaba abierta.
        */
        return back();
    }

    public function destroy(
        Request $request,
        PracticeRoutine $practiceRoutine
    ) {
        $user = $request->user();

        abort_unless(
            (int) $practiceRoutine->user_id === (int) $user->id,
            404
        );

        /*
        * Nunca puede eliminarse la única rutina.
        */
        abort_if(
            $user->practiceRoutines()->count() <= 1,
            422,
            'You cannot delete your only routine.'
        );

        $activeRoutineId = $request->integer(
            'active_routine_id'
        );

        $redirectRoutine = DB::transaction(
            function () use (
                $user,
                $practiceRoutine,
                $activeRoutineId
            ) {
                $deletedRoutineId = $practiceRoutine->id;
                $wasDefault = (bool) $practiceRoutine->is_default;

                /*
                * Elegimos una rutina de reemplazo:
                * primero la siguiente por position,
                * y si no existe, la anterior.
                */
                $replacementRoutine =
                    $user->practiceRoutines()
                        ->whereKeyNot($deletedRoutineId)
                        ->where('position', '>', $practiceRoutine->position)
                        ->orderBy('position')
                        ->orderBy('id')
                        ->first()
                    ?? $user->practiceRoutines()
                        ->whereKeyNot($deletedRoutineId)
                        ->orderByDesc('position')
                        ->orderByDesc('id')
                        ->firstOrFail();

                /*
                * Los steps deberían eliminarse mediante
                * la cascada de la foreign key.
                */
                $practiceRoutine->delete($practiceRoutine->id);

                /*
                * Si borramos la default, la rutina de reemplazo
                * se convierte en la nueva default.
                */
                if ($wasDefault) {
                    $replacementRoutine->update([
                        'is_default' => true,
                    ]);
                }

                /*
                * Si la rutina activa no fue la eliminada
                * y todavía pertenece al usuario, la conservamos.
                */
                if (
                    $activeRoutineId
                    && $activeRoutineId !== $deletedRoutineId
                ) {
                    $currentActiveRoutine =
                        $user->practiceRoutines()
                            ->whereKey($activeRoutineId)
                            ->first();

                    if ($currentActiveRoutine) {
                        return $currentActiveRoutine;
                    }
                }

                return $replacementRoutine;
            }
        );

        return redirect()->route('welcome', [
            'routine' => $redirectRoutine->id,
        ]);
    }

    public function move(
        Request $request,
        PracticeRoutine $practiceRoutine
    ) {
        $user = $request->user();

        abort_unless(
            (int) $practiceRoutine->user_id === (int) $user->id,
            404
        );

        $validated = $request->validate([
            'direction' => [
                'required',
                'in:up,down',
            ],
        ]);

        DB::transaction(function () use (
            $user,
            $practiceRoutine,
            $validated
        ) {
            $routines = $user->practiceRoutines()
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $currentIndex = $routines->search(
                fn ($routine) =>
                    $routine->id === $practiceRoutine->id
            );

            if ($currentIndex === false) {
                abort(404);
            }

            $targetIndex = $validated['direction'] === 'up'
                ? $currentIndex - 1
                : $currentIndex + 1;

            if (! $routines->has($targetIndex)) {
                return;
            }

            $targetRoutine = $routines[$targetIndex];

            $currentPosition = $practiceRoutine->position;

            $practiceRoutine->position =
                $targetRoutine->position;

            $targetRoutine->position =
                $currentPosition;

            $practiceRoutine->save();
            $targetRoutine->save();
        });

        return back();
    }
}