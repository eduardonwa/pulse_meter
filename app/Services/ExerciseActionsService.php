<?php

namespace App\Services;

use App\Models\PracticeRoutine;
use App\Models\PracticeRoutineStep;
use Illuminate\Support\Facades\DB;

class ExerciseActionsService
{
    public function move(PracticeRoutine $routine, int $exerciseId, string $direction): void
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 422);

        DB::transaction(function () use ($routine, $exerciseId, $direction): void {
            $steps = $routine->steps()->lockForUpdate()->get()->values();
            $index = $steps->search(fn (PracticeRoutineStep $step) => (int) $step->id === $exerciseId);
            abort_if($index === false, 404);

            $neighbor = $steps->get($index + ($direction === 'up' ? -1 : 1));
            if (! $neighbor) return;

            $current = $steps[$index];
            $position = $current->position;
            $current->position = $neighbor->position;
            $neighbor->position = $position;
            $current->save();
            $neighbor->save();
        });
    }

    public function duplicate(PracticeRoutine $routine, int $exerciseId, int $limit): PracticeRoutineStep
    {
        return DB::transaction(function () use ($routine, $exerciseId, $limit): PracticeRoutineStep {
            $steps = $routine->steps()->lockForUpdate()->get();
            abort_if($steps->count() >= $limit, 422, 'Exercise limit reached');

            $original = $steps->first(fn (PracticeRoutineStep $step) => (int) $step->id === $exerciseId);
            abort_if(! $original, 404);

            $position = $original->position + 1;
            $routine->steps()->where('position', '>=', $position)->increment('position');

            $copy = $original->replicate(['practice_routine_id', 'position', 'created_at', 'updated_at']);
            $copy->name = mb_substr(trim($original->name) . ' copy', 0, 80);
            $copy->position = $position;
            $copy->origin = 'custom';
            $routine->steps()->save($copy);
            return $copy->refresh();
        });
    }

    public function delete(PracticeRoutine $routine, int $exerciseId): void
    {
        DB::transaction(function () use ($routine, $exerciseId): void {
            $steps = $routine->steps()->lockForUpdate()->get();
            abort_if($steps->count() <= 1, 422, 'A routine must contain at least one exercise.');

            $step = $steps->first(fn (PracticeRoutineStep $item) => (int) $item->id === $exerciseId);
            abort_if(! $step, 404);

            $position = $step->position;
            $step->delete();
            $routine->steps()->where('position', '>', $position)->decrement('position');
        });
    }
}
