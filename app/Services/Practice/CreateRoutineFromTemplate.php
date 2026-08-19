<?php

namespace App\Services\Practice;

use App\Models\PracticeRoutine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateRoutineFromTemplate
{
    public function create(
        User $user,
        int $routineTemplateId,
        string $name,
        array $steps
    ): PracticeRoutine {
        return DB::transaction(function () use (
            $routineTemplateId, $user,
            $name,
            $steps,
        ) {
            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            $lastPosition = $lockedUser
                ->practiceRoutines()
                ->max('position');

            $routine = $lockedUser
                ->practiceRoutines()
                ->create([
                    'routine_template_id' => $routineTemplateId,
                    'name' => $name,
                    'position' => $lastPosition === null
                        ? 0
                        : $lastPosition + 1,
                    'is_default' => false,
                ]);

            $routine->steps()->createMany(
                collect($steps)
                    ->values()
                    ->map(fn ($step, $position) => [
                        'name' => trim($step['name']),
                        'bpm' => (int) $step['bpm'],
                        'mode' => $step['mode'],

                        'duration_seconds' =>
                            $step['mode'] === 'timer'
                                ? (int) $step['duration_seconds']
                                : null,

                        'position' => $position,
                        'origin' => 'custom',
                    ])
                    ->all()
            );

            return $routine;
        });
    }
}