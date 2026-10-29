<?php

namespace Tests\Feature;

use App\Http\Controllers\PracticeRoutineStepController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeRoutineStepUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function setupStep(string $source): array
    {
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authorize::class);
        $user = User::factory()->create();
        $routine = $user->practiceRoutines()->create([
            'name' => 'Test Routine', 'position' => 0, 'is_default' => true,
        ]);
        $step = $routine->steps()->create([
            'name' => 'Alternate Picking', 'bpm' => 160,
            'mode' => 'drill', 'duration_seconds' => 60,
            'position' => 0, 'origin' => 'custom',
            'alpha_tex' => 'original', 'tab_source' => $source,
        ]);
        return [$user, $step];
    }

    private function updateUrl($step): string
    {
        return action([PracticeRoutineStepController::class, 'update'], [
            'practiceRoutineStep' => $step,
        ]);
    }

    public function test_user_tablature_can_be_updated(): void
    {
        [$user, $step] = $this->setupStep('user');
        $this->actingAs($user)->patchJson($this->updateUrl($step), [
            'name' => 'Updated Exercise', 'bpm' => 180, 'mode' => 'drill',
            'duration_seconds' => 90, 'alpha_tex' => ':8 5.1 7.1',
        ])->assertOk()->assertJsonPath('tab_source', 'user');
        $this->assertSame(':8 5.1 7.1', $step->fresh()->alpha_tex);
    }

    public function test_template_tablature_cannot_be_modified(): void
    {
        [$user, $step] = $this->setupStep('template');
        $this->actingAs($user)->patchJson($this->updateUrl($step), [
            'name' => 'Updated Exercise', 'bpm' => 180, 'mode' => 'drill',
            'duration_seconds' => 90, 'alpha_tex' => ':8 5.1',
        ])->assertForbidden();
        $this->assertSame('original', $step->fresh()->alpha_tex);
    }

    public function test_updating_bpm_does_not_modify_existing_template_tablature(): void
    {
        [$user, $step] = $this->setupStep('template');
        $this->actingAs($user)->patchJson($this->updateUrl($step), [
            'name' => 'Updated Exercise', 'bpm' => 180, 'mode' => 'drill',
            'duration_seconds' => 90,
        ])->assertOk();
        $this->assertSame('original', $step->fresh()->alpha_tex);
        $this->assertSame('template', $step->fresh()->tab_source);
    }
}
