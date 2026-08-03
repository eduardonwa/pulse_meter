<?php

namespace App\Livewire;

use App\Models\PracticeRoutineStep;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RoutineExercises extends Component
{
    #[Locked]
    public int $routineId;

    public string $routineName = '';
    public array $exercises = [];

    public int $exerciseLimit;

    public function mount(int $routineId): void
    {
        Gate::authorize('use-pro');

        $this->routineId = $routineId;
        $this->exerciseLimit = Auth::user()->exerciseLimit();
        
        $this->refreshExercises();
    }

    private function refreshExercises(): void
    {
        $routine = Auth::user()
            ->practiceRoutines()
            ->whereKey($this->routineId)
            ->firstOrFail();

        $this->routineName = $routine->name;

        $this->exercises = $routine->steps()
            ->select([
                'id',
                'name',
                'bpm',
                'mode',
                'duration_seconds',
                'position',
                'origin',
            ])
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (PracticeRoutineStep $exercise) => [
                'id' => $exercise->id,
                'name' => $exercise->name,
                'bpm' => $exercise->bpm,
                'mode' => $exercise->mode,
                'duration_seconds' =>
                    $exercise->duration_seconds,
                'position' => $exercise->position,
                'origin' => $exercise->origin,
            ])
            ->values()
            ->all();
    }

    public function moveExercise(
        int $exerciseId,
        string $direction,
    ): void {
        Gate::authorize('use-pro');

        abort_unless(
            in_array($direction, ['up', 'down'], true),
            422
        );

        $routine = Auth::user()
            ->practiceRoutines()
            ->whereKey($this->routineId)
            ->firstOrFail();

        DB::transaction(function () use (
            $routine,
            $exerciseId,
            $direction
        ): void {
            $exercises = $routine->steps()
                ->orderBy('position')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $currentIndex = $exercises->search(
                fn (PracticeRoutineStep $exercise) =>
                    (int) $exercise->id === $exerciseId
            );

            abort_if($currentIndex === false, 404);

            $targetIndex = $direction === 'up'
                ? $currentIndex - 1
                : $currentIndex + 1;

            /*
            * Si el ejercicio ya está en el extremo,
            * no hay nada que mover.
            */
            if (! $exercises->has($targetIndex)) {
                return;
            }

            $currentExercise = $exercises[$currentIndex];
            $targetExercise = $exercises[$targetIndex];

            $currentPosition = $currentExercise->position;

            $currentExercise->position =
                $targetExercise->position;

            $targetExercise->position =
                $currentPosition;

            $currentExercise->save();
            $targetExercise->save();
        });

        $this->refreshExercises();

        $this->dispatch(
            'routine-exercises-updated',
            routineId: $this->routineId,
            exercises: $this->exercises,
        );
    }

    public function duplicateExercise(int $exerciseId): void
    {
        Gate::authorize('use-pro');

        $routine = Auth::user()
            ->practiceRoutines()
            ->whereKey($this->routineId)
            ->firstOrFail();

        DB::transaction(function () use (
            $routine,
            $exerciseId
        ): void {
            $exercises = $routine->steps()
                ->orderBy('position')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $limit = Auth::user()->exerciseLimit();

            abort_if(
                $exercises->count() >= $limit,
                422,
                "This routine can contain up to {$limit} exercises."
            );

            $original = $exercises->first(
                fn (PracticeRoutineStep $exercise) =>
                    (int) $exercise->id === $exerciseId
            );

            abort_if($original === null, 404);

            $copyPosition = $original->position + 1;

            /*
            * Abrimos una posición justo después
            * del ejercicio original.
            */
            $routine->steps()
                ->where('position', '>=', $copyPosition)
                ->increment('position');

            /*
            * replicate() crea una copia sin guardar
            * del modelo original.
            */
            $copy = $original->replicate([
                'practice_routine_id',
                'position',
                'created_at',
                'updated_at',
            ]);

            $copy->name = mb_substr(
                trim($original->name) . ' copy',
                0,
                80
            );

            $copy->position = $copyPosition;

            /*
            * La relación asigna practice_routine_id
            * a la misma rutina.
            */
            $routine->steps()->save($copy);
        });

        $this->refreshExercises();

        $this->dispatch(
            'routine-exercises-updated',
            routineId: $this->routineId,
            exercises: $this->exercises,
        );
    }

    public function deleteExercise(int $exerciseId): void
    {
        Gate::authorize('use-pro');

        $routine = Auth::user()
            ->practiceRoutines()
            ->whereKey($this->routineId)
            ->firstOrFail();

        DB::transaction(function () use (
            $routine,
            $exerciseId
        ): void {
            $exercises = $routine->steps()
                ->orderBy('position')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            abort_if(
                $exercises->count() <= 1,
                422,
                'A routine must contain at least one exercise.'
            );

            $exercise = $exercises->first(
                fn (PracticeRoutineStep $item) =>
                    (int) $item->id === $exerciseId
            );

            abort_if($exercise === null, 404);

            $deletedPosition = $exercise->position;

            $exercise->delete();

            $routine->steps()
                ->where('position', '>', $deletedPosition)
                ->decrement('position');
        });

        $this->refreshExercises();

        $this->dispatch(
            'routine-exercises-updated',
            routineId: $this->routineId,
            exercises: $this->exercises,
        );
    }

    public function render(): View
    {
        return view('livewire.routine-exercises');
    }
}