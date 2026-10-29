<?php

namespace App\Livewire;

use App\Models\PracticeRoutineStep;
use App\Services\ExerciseActionsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
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
                'alpha_tex',
                'tab_source',
                'time_signature_numerator',
                'time_signature_denominator',
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
                'alpha_tex' => $exercise->alpha_tex,
                'tab_source' => $exercise->tab_source,
                'time_signature_numerator' => $exercise->time_signature_numerator,
                'time_signature_denominator' => $exercise->time_signature_denominator,
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

        app(ExerciseActionsService::class)->move($routine, $exerciseId, $direction);

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

        app(ExerciseActionsService::class)->duplicate($routine, $exerciseId, Auth::user()->exerciseLimit());

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

        app(ExerciseActionsService::class)->delete($routine, $exerciseId);

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