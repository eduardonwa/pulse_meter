<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeRoutineStepCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_custom_step_ignores_submitted_alpha_tex(): void
    {
        $this->withoutMiddleware(
            Authorize::class
        );

        $user = User::factory()->create();

        $routine = $user
            ->practiceRoutines()
            ->create([
                'name' => 'Test Routine',
                'position' => 0,
                'is_default' => true,
            ]);

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('practice-routine-steps.store', [
                    'practiceRoutine' => $routine,
                ]),
                [
                    'name' => 'Custom Exercise',
                    'bpm' => 160,
                    'mode' => 'timer',
                    'duration_seconds' => 60,

                    // Todavía no permitimos crear patterns propios.
                    'alpha_tex' => <<<'ALPHATEX'
\staff {tabs}

:16
1.1 2.1 3.1 4.1
ALPHATEX,
                ]
            );

        $response->assertCreated();

        $step = $routine
            ->steps()
            ->where('name', 'Custom Exercise')
            ->firstOrFail();

        $this->assertSame(
            160,
            $step->bpm
        );

        $this->assertNull(
            $step->alpha_tex
        );
    }
}