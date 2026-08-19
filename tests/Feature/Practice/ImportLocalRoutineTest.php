<?php

namespace Tests\Feature\Practice;

use App\Models\PracticeRoutine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportLocalRoutineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_the_existing_free_local_routine_only(): void
    {
        $user = $this->createUser([
            'plan' => 'pro',
        ]);

        $freeRoutine = $this->createRoutine(
            $user,
            [
                'name' => 'My Exercises',
                'position' => 0,
                'is_default' => true,

                'sync_source' =>
                    PracticeRoutine::SYNC_SOURCE_FREE_LOCAL,
            ],
            [
                [
                    'name' => 'OLD FREE EXERCISE',
                    'bpm' => 90,
                ],
            ],
        );

        $normalRoutine = $this->createRoutine(
            $user,
            [
                'name' => 'Songs',
                'position' => 1,
                'is_default' => false,
            ],
            [
                [
                    'name' => 'Song Exercise',
                    'bpm' => 170,
                ],
            ],
        );

        $response = $this
            ->actingAs($user)
            ->postJson(
                '/practice-routines/import-local',
                [
                    'steps' => $this->localSteps(),
                ],
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'routine.id',
                $freeRoutine->id,
            );

        $this->assertSame(
            [
                'LOCAL TEST',
                'ONLY FREE',
            ],
            $freeRoutine
                ->steps()
                ->pluck('name')
                ->all(),
        );

        /*
         * Las demás rutinas de Trial/Pro
         * permanecen completamente intactas.
         */
        $this->assertSame(
            [
                'Song Exercise',
            ],
            $normalRoutine
                ->steps()
                ->pluck('name')
                ->all(),
        );

        $this->assertSame(
            2,
            $user->practiceRoutines()->count(),
        );
    }

    public function test_repeated_sync_updates_the_same_free_local_routine(): void
    {
        $user = $this->createUser([
            'plan' => 'pro',
        ]);

        $routine = $this->createRoutine(
            $user,
            [
                'name' => 'My Exercises',
                'position' => 0,
                'is_default' => true,
            ],
            [
                [
                    'name' => 'SERVER OLD',
                    'bpm' => 100,
                ],
            ],
        );

        $this
            ->actingAs($user)
            ->postJson(
                '/practice-routines/import-local',
                [
                    'steps' => $this->localSteps(),
                ],
            )
            ->assertOk();

        $response = $this
            ->actingAs($user)
            ->postJson(
                '/practice-routines/import-local',
                [
                    'steps' => [
                        [
                            'name' => 'FREE CHANGED AGAIN',
                            'bpm' => 200,
                            'mode' => 'timer',
                            'duration_seconds' => 45,
                        ],
                    ],
                ],
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'routine.id',
                $routine->id,
            )
            ->assertJsonCount(1, 'routine.steps');

        $this->assertSame(
            1,
            $user->practiceRoutines()->count(),
        );

        $step = $routine
            ->steps()
            ->sole();

        $this->assertSame(
            'FREE CHANGED AGAIN',
            $step->name,
        );

        $this->assertSame(200, $step->bpm);
        $this->assertSame(45, $step->duration_seconds);
    }

    public function test_it_creates_a_free_local_routine_without_touching_existing_routines(): void
    {
        $user = $this->createUser([
            'plan' => 'pro',
        ]);

        $firstRoutine = $this->createRoutine(
            $user,
            [
                'name' => 'Technique',
                'position' => 0,
                'is_default' => true,
            ],
            [
                [
                    'name' => 'Technique Exercise',
                ],
            ],
        );

        $secondRoutine = $this->createRoutine(
            $user,
            [
                'name' => 'Songs',
                'position' => 1,
                'is_default' => false,
            ],
            [
                [
                    'name' => 'Song Exercise',
                ],
            ],
        );

        $response = $this
            ->actingAs($user)
            ->postJson(
                '/practice-routines/import-local',
                [
                    'steps' => $this->localSteps(),
                ],
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'status',
                'imported',
            )
            ->assertJsonPath(
                'routine.sync_source',
                PracticeRoutine::SYNC_SOURCE_FREE_LOCAL,
            );

        /*
        * Las rutinas existentes siguen siendo normales.
        */
        $this->assertNull(
            $firstRoutine->fresh()->sync_source,
        );

        $this->assertNull(
            $secondRoutine->fresh()->sync_source,
        );

        $this->assertSame(
            ['Technique Exercise'],
            $firstRoutine
                ->steps()
                ->pluck('name')
                ->all(),
        );

        $this->assertSame(
            ['Song Exercise'],
            $secondRoutine
                ->steps()
                ->pluck('name')
                ->all(),
        );

        /*
        * Ahora existe una tercera rutina:
        * la copia de Free.
        */
        $this->assertSame(
            3,
            $user->practiceRoutines()->count(),
        );

        $freeRoutine = $user
            ->practiceRoutines()
            ->where(
                'sync_source',
                PracticeRoutine::SYNC_SOURCE_FREE_LOCAL,
            )
            ->sole();

        $this->assertSame(
            ['LOCAL TEST', 'ONLY FREE'],
            $freeRoutine
                ->steps()
                ->pluck('name')
                ->all(),
        );
    }

    public function test_trial_cannot_import_free_routine_when_routine_limit_is_reached(): void
    {
        $user = $this->createUser([
            'plan' => 'free',
        ]);

        // Aquí necesitas activar Trial de la misma manera
        // que ya usan tus otros tests de Trial.

        $this->createRoutine($user, [
            'name' => 'Routine 1',
            'position' => 0,
            'is_default' => true,
        ]);

        $this->createRoutine($user, [
            'name' => 'Routine 2',
            'position' => 1,
        ]);

        $this->createRoutine($user, [
            'name' => 'Routine 3',
            'position' => 2,
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(
                '/practice-routines/import-local',
                [
                    'steps' => $this->localSteps(),
                ],
            );

        $response
            ->assertStatus(409)
            ->assertJsonPath(
                'status',
                'not_imported',
            )
            ->assertJsonPath(
                'reason',
                'trial_routine_limit',
            );

        $this->assertSame(
            3,
            $user->practiceRoutines()->count(),
        );

        $this->assertFalse(
            $user
                ->practiceRoutines()
                ->where(
                    'sync_source',
                    PracticeRoutine::SYNC_SOURCE_FREE_LOCAL,
                )
                ->exists(),
        );
    }

    public function test_free_user_cannot_import_local_exercises(): void
    {
        $user = $this->createUser([
            'plan' => 'free',
        ]);

        $this
            ->actingAs($user)
            ->postJson(
                '/practice-routines/import-local',
                [
                    'steps' => $this->localSteps(),
                ],
            )
            ->assertForbidden();
    }

    private function createRoutine(
        User $user,
        array $attributes = [],
        array $steps = [],
    ): PracticeRoutine {
        $routine = $user
            ->practiceRoutines()
            ->create([
                'name' => 'Routine',
                'position' => 0,
                'is_default' => false,
                'sync_source' => null,
                ...$attributes,
            ]);

        foreach ($steps as $position => $step) {
            $routine->steps()->create([
                'name' => 'Exercise ' . ($position + 1),
                'bpm' => 100,
                'mode' => 'timer',
                'duration_seconds' => 60,
                'position' => $position,
                'origin' => 'custom',
                ...$step,
            ]);
        }

        return $routine;
    }

    private function localSteps(): array
    {
        return [
            [
                'name' => 'LOCAL TEST',
                'bpm' => 145,
                'mode' => 'timer',
                'duration_seconds' => 90,
            ],
            [
                'name' => 'ONLY FREE',
                'bpm' => 120,
                'mode' => 'classic',
                'duration_seconds' => null,
            ],
        ];
    }

    private function createUser(
        array $attributes = [],
    ): User {
        /** @var User $user */
        $user = User::factory()->create(
            $attributes,
        );

        return $user;
    }
}