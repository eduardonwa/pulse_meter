<?php

namespace Tests\Feature\Practice;

use App\Models\TrialEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TablatureAuthoringAccessTest extends TestCase
{
    use RefreshDatabase;

    private function exerciseData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Picking',
            'bpm' => 100,
            'mode' => 'drill',
            'duration_seconds' => 60,
        ], $overrides);
    }

    private function createRoutine(User $user)
    {
        return $user->practiceRoutines()->create([
            'name' => 'Routine',
            'position' => 0,
            'is_default' => true,
        ]);
    }

    public function test_guest_cannot_access_authoring_endpoint(): void
    {
        $owner = User::factory()->create(['plan' => 'pro']);
        $routine = $this->createRoutine($owner);

        $this->postJson(route('practice-routine-steps.store', $routine),
            $this->exerciseData(['alpha_tex' => 'test'])
        )->assertUnauthorized();
    }

    public function test_free_user_cannot_create_a_tablature_on_the_server(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        $routine = $this->createRoutine($user);

        $this->actingAs($user)->postJson(
            route('practice-routine-steps.store', $routine),
            $this->exerciseData(['alpha_tex' => 'test'])
        )->assertForbidden();

        $this->assertDatabaseCount('practice_routine_steps', 0);
    }

    public function test_free_user_cannot_update_a_tablature_on_the_server(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        $routine = $this->createRoutine($user);
        $step = $routine->steps()->create([
            ...$this->exerciseData(),
            'position' => 0,
            'origin' => 'custom',
        ]);

        $this->actingAs($user)->patchJson(
            route('practice-routine-steps.update', $step),
            $this->exerciseData(['alpha_tex' => 'test'])
        )->assertForbidden();

        $this->assertNull($step->fresh()->alpha_tex);
    }

    public function test_pro_user_can_create_and_update_tablatures(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);
        $routine = $this->createRoutine($user);

        $response = $this->actingAs($user)->postJson(
            route('practice-routine-steps.store', $routine),
            $this->exerciseData(['alpha_tex' => 'tab one'])
        )->assertCreated();

        $this->assertSame('tab one', $response->json('alpha_tex'));

        $step = $routine->steps()->firstOrFail();

        $this->actingAs($user)->patchJson(
            route('practice-routine-steps.update', $step),
            $this->exerciseData(['alpha_tex' => 'tab two'])
        )->assertOk();

        $this->assertSame('tab two', $step->fresh()->alpha_tex);
    }

    public function test_active_trial_has_tablature_authoring_access(): void
    {
        $user = User::factory()->create(['plan' => 'free']);

        $trial = new TrialEntitlement();
        $trial->forceFill([
            'status' => 'active',
            'granted_seconds' => 3600,
            'used_seconds' => 0,
            'started_at' => now(),
            'expires_at' => now()->addDays(15),
        ]);
        $trial->user()->associate($user);
        $trial->save();

        $routine = $this->createRoutine($user);

        $this->actingAs($user)->postJson(
            route('practice-routine-steps.store', $routine),
            $this->exerciseData(['alpha_tex' => 'trial tab'])
        )->assertCreated();
    }
}
