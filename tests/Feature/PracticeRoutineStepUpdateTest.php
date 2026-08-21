<?php

namespace Tests\Feature;

use App\Http\Controllers\PracticeRoutineStepController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeRoutineStepUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_step_preserves_alpha_tex_and_ignores_submitted_alpha_tex(): void
    {
        $this->withoutMiddleware(
            \Illuminate\Auth\Middleware\Authorize::class
        );

        $user = User::factory()->create();

        $routine = $user
            ->practiceRoutines()
            ->create([
                'name' => 'Test Routine',
                'position' => 0,
                'is_default' => true,
            ]);

        $originalAlphaTex = <<<'ALPHATEX'
\staff {tabs}

:16
5.1 6.1 7.1 8.1 9.1
ALPHATEX;

        $step = $routine
            ->steps()
            ->create([
                'name' => 'Alternate Picking',
                'bpm' => 160,
                'mode' => 'timer',
                'duration_seconds' => 60,
                'position' => 0,
                'origin' => 'custom',
                'alpha_tex' => $originalAlphaTex,
            ]);

            $this->assertSame(
                $user->id,
                (int) $routine->user_id
            );

            $this->assertSame(
                $routine->id,
                (int) $step->practice_routine_id
            );

            $this->assertNotNull(
                $step->routine
            );

            $this->assertSame(
                $user->id,
                (int) $step->routine->user_id
            );

        $response = $this
            ->actingAs($user)
            ->patchJson(
                action(
                    [
                        PracticeRoutineStepController::class,
                        'update',
                    ],
                    [
                        'practiceRoutineStep' => $step,
                    ]
                ),
                [
                    'name' => 'Updated Exercise',
                    'bpm' => 180,
                    'mode' => 'timer',
                    'duration_seconds' => 90,

                    // Intento de modificar el pattern.
                    'alpha_tex' => 'MALICIOUS PATTERN',
                ]
            );

        $response->assertOk();

        $step->refresh();

        $this->assertSame(
            'Updated Exercise',
            $step->name
        );

        $this->assertSame(
            180,
            $step->bpm
        );

        $this->assertSame(
            90,
            $step->duration_seconds
        );

        $this->assertSame(
            $originalAlphaTex,
            $step->alpha_tex
        );

        $this->assertNotSame(
            'MALICIOUS PATTERN',
            $step->alpha_tex
        );
    }
}