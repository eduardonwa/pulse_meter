<?php

namespace App\Services\Practice;

use App\Models\PracticeRoutine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreatePracticeRoutine
{
    public function create(User $user): ?PracticeRoutine
    {
        return DB::transaction(
            function () use ($user): ?PracticeRoutine {
                /*
                 * Serializa las creaciones del mismo usuario.
                 * Dos requests simultáneos no pueden saltarse
                 * el límite contando ambos antes de crear.
                 */
                $lockedUser = User::query()
                    ->lockForUpdate()
                    ->findOrFail($user->getKey());

                $limit = $lockedUser->routineLimit();

                if (
                    $limit !== null
                    && $lockedUser
                        ->practiceRoutines()
                        ->count() >= $limit
                ) {
                    return null;
                }

                $lastPosition = $lockedUser
                    ->practiceRoutines()
                    ->max('position');

                $nextPosition = $lastPosition === null
                    ? 0
                    : $lastPosition + 1;

                $isFirstRoutine = $lockedUser
                    ->practiceRoutines()
                    ->doesntExist();

                $routine = $lockedUser
                    ->practiceRoutines()
                    ->create([
                        'name' =>
                            'Routine ' . ($nextPosition + 1),
                        'position' => $nextPosition,
                        'is_default' => $isFirstRoutine,
                    ]);

                $routine->steps()->create([
                    'name' => 'New Exercise',
                    'bpm' => 100,
                    'mode' => 'timer',
                    'duration_seconds' => 60,
                    'position' => 0,
                    'origin' => 'custom',
                ]);

                return $routine;
            }
        );
    }
}