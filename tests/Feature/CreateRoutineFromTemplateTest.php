<?php

namespace Tests\Feature;

use App\Models\RoutineTemplate;
use App\Models\User;
use App\Services\Practice\CreateRoutineFromTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateRoutineFromTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_copies_alpha_tex_from_template_step_to_practice_routine_step(): void
    {
        $user = User::factory()->create();

        $template = RoutineTemplate::create([
            'user_id' => $user->id,
            'difficulty' => 'beginner',
        ]);

        $alphaTex = <<<'ALPHATEX'
\staff {tabs}

:16
5.1 6.1 7.1 8.1 9.1
ALPHATEX;

        $template->steps()->create([
            'name_es' => 'Alternate Picking',
            'name_en' => 'Alternate Picking',
            'bpm' => 160,
            'mode' => 'timer',
            'duration_seconds' => 60,
            'position' => 0,
            'alpha_tex' => $alphaTex,
        ]);

        $creator = app(CreateRoutineFromTemplate::class);

        $routine = $creator->create(
            $user,
            $template,
            'Copied Routine',
            [
                [
                    'name' => 'Alternate Picking',
                    'bpm' => 180,
                    'mode' => 'timer',
                    'duration_seconds' => 90,
                ],
            ],
        );

        $step = $routine
            ->steps()
            ->firstOrFail();

        $this->assertSame(
            $alphaTex,
            $step->alpha_tex
        );

        $this->assertSame(
            180,
            $step->bpm
        );

        $this->assertSame(
            90,
            $step->duration_seconds
        );
    }

    public function test_it_ignores_alpha_tex_sent_with_steps_and_uses_template_pattern(): void
    {
        $user = User::factory()->create();

        $template = RoutineTemplate::create([
            'user_id' => $user->id,
            'difficulty' => 'beginner',
        ]);

        $officialAlphaTex = <<<'ALPHATEX'
    \staff {tabs}

    :16
    5.1 6.1 7.1 8.1 9.1
    ALPHATEX;

        $template->steps()->create([
            'name_es' => 'Alternate Picking',
            'name_en' => 'Alternate Picking',
            'bpm' => 160,
            'mode' => 'timer',
            'duration_seconds' => 60,
            'position' => 0,
            'alpha_tex' => $officialAlphaTex,
        ]);

        $creator = app(CreateRoutineFromTemplate::class);

        $routine = $creator->create(
            $user,
            $template,
            'Copied Routine',
            [
                [
                    'name' => 'Alternate Picking',
                    'bpm' => 160,
                    'mode' => 'timer',
                    'duration_seconds' => 60,

                    // Intento de manipulación desde frontend.
                    'alpha_tex' => 'MALICIOUS PATTERN',
                ],
            ],
        );

        $step = $routine
            ->steps()
            ->firstOrFail();

        $this->assertSame(
            $officialAlphaTex,
            $step->alpha_tex
        );

        $this->assertNotSame(
            'MALICIOUS PATTERN',
            $step->alpha_tex
        );
    }
}