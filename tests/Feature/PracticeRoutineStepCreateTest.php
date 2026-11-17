<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeRoutineStepCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_custom_step_saves_user_alpha_tex(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);
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

    public function test_pro_can_save_full_musicxml_without_flattening_unsupported_notation(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);
        $routine = $user->practiceRoutines()->create([
            'name' => 'Test Routine', 'position' => 0, 'is_default' => true,
        ]);
        $source = '<score-partwise><part><measure number="1"><note><notations><technical>'
            . '<tap>1</tap></technical></notations></note></measure></part></score-partwise>';

        $response = $this->actingAs($user)->postJson(
            route('practice-routine-steps.store', ['practiceRoutine' => $routine]),
            [
                'name' => 'Whole score', 'bpm' => 120, 'mode' => 'classic',
                'alpha_tex' => null, 'musicxml_source' => $source,
            ],
        );

        $response->assertCreated()->assertJsonPath('tab_source', 'user')
            ->assertJsonPath('musicxml_source', $source);
        $step = $routine->steps()->firstOrFail();
        $this->assertNull($step->alpha_tex);
        $this->assertSame($source, $step->musicxml_source);
    }

    public function test_cannot_save_two_score_representations_together(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);
        $routine = $user->practiceRoutines()->create([
            'name' => 'Test Routine', 'position' => 0, 'is_default' => true,
        ]);
        $this->actingAs($user)->postJson(
            route('practice-routine-steps.store', ['practiceRoutine' => $routine]),
            [
                'name' => 'Two scores', 'bpm' => 120, 'mode' => 'classic',
                'alpha_tex' => ':4 5.1',
                'musicxml_source' => '<score-partwise/>',
            ],
        )->assertStatus(422);
    }

    public function test_creating_step_without_tablature_leaves_source_null(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);
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
