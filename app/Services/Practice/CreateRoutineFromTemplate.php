<?php

namespace App\Services\Practice;

use App\Models\PracticeRoutine;
use App\Models\RoutineTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateRoutineFromTemplate
{
    public function create(
        User $user,
        RoutineTemplate $routineTemplate,
        string $name,
        array $steps
    ): PracticeRoutine {
        return DB::transaction(function () use (
            $routineTemplate,
            $user,
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
                    'routine_template_id' => $routineTemplate->id,
                    'name' => $name,
                    'position' => $lastPosition === null
                        ? 0
                        : $lastPosition + 1,
                    'is_default' => false,
                ]);

            $templateSteps = $routineTemplate
                ->steps()
                ->orderBy('position')
                ->get()
                ->values();

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

                        'alpha_tex' =>
                            $templateSteps
                                ->get($position)
                                ?->alpha_tex,

                        'position' => $position,
                        'origin' => 'custom',
                    ])
                    ->all()
            );

            return $routine;
        });
    }
}