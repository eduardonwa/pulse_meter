<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeRoutineStepCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_custom_step_saves_user_alpha_tex(): void
    {
        $this->withoutMiddleware(Authorize::class);
        $user = User::factory()->create();
        $routine = $user->practiceRoutines()->create([
            'name' => 'Test Routine', 'position' => 0, 'is_default' => true,
        ]);

        $response = $this->actingAs($user)->postJson(
            route('practice-routine-steps.store', ['practiceRoutine' => $routine]),
            [
                'name' => 'Custom Exercise',
                'bpm' => 160,
                'mode' => 'drill',
                'duration_seconds' => 60,
                'alpha_tex' => '\\staff {tabs}' . "\n" . ':16 1.1 2.1 3.1 4.1',
            ]
        );

        $response->assertCreated()->assertJsonPath('tab_source', 'user');
        $step = $routine->steps()->firstOrFail();
        $this->assertSame('user', $step->tab_source);
        $this->assertSame('\\staff {tabs}' . "\n" . ':16 1.1 2.1 3.1 4.1', $step->alpha_tex);
    }

    public function test_creating_step_without_tablature_leaves_source_null(): void
    {
        $this->withoutMiddleware(Authorize::class);
        $user = User::factory()->create();
        $routine = $user->practiceRoutines()->create([
            'name' => 'Test Routine', 'position' => 0, 'is_default' => true,
        ]);
        $this->actingAs($user)->postJson(
            route('practice-routine-steps.store', ['practiceRoutine' => $routine]),
            ['name' => 'Regular Exercise', 'bpm' => 100, 'mode' => 'classic']
        )->assertCreated();
        $this->assertNull($routine->steps()->firstOrFail()->tab_source);
    }
}
