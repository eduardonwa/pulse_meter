<?php

namespace Tests\Feature\Pulse;

use App\Models\PulsePreset;
use App\Models\TrialEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PulsePresetAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createProUser(): User
    {
        return User::factory()->create([
            'plan' => 'pro',
        ]);
    }

    private function createFreeUser(): User
    {
        return $user = User::factory()->create([
            'plan' => 'free',
        ]);
    }

    private function createActiveTrialUser(): User
    {
        $user = $this->createFreeUser();

        $trial = new TrialEntitlement;

        $trial->forceFill([
            'status' => 'active',
            'granted_seconds' => 3600,
            'used_seconds' => 0,
            'started_at' => now(),
            'expires_at' => now()->addDays(15),
        ]);

        $trial->user()->associate($user);
        $trial->save();

        return $user;
    }

    private function validPresetPayload(
        string $name = 'Three Pulse'
    ): array {
        return [
            'name' => $name,
            'numerator' => 3,
            'denominator' => 4,
            'grouping' => [3],
            'pattern' => [
                [
                    'sound' => 'accent',
                    'groupStart' => true,
                ],
                [
                    'sound' => 'click',
                    'groupStart' => false,
                ],
                [
                    'sound' => 'click',
                    'groupStart' => false,
                ],
            ],
        ];
    }

    private function createPresetFor(
        User $user,
        string $name = 'Existing Preset'
    ): PulsePreset {
        return $user->pulsePresets()->create([
            ...$this->validPresetPayload($name),
            'position' => 1,
        ]);
    }

    public function test_free_user_cannot_access_server_preset_routes(): void
    {
        $user = $this->createFreeUser();

        $preset = $this->createPresetFor($user);

        $this
            ->actingAs($user)
            ->getJson(route('pulse-presets.index'))
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->postJson(
                route('pulse-presets.store'),
                $this->validPresetPayload('New Preset')
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->patchJson(
                route('pulse-presets.update', $preset),
                [
                    'name' => 'Renamed Preset',
                ]
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->deleteJson(
                route('pulse-presets.destroy', $preset)
            )
            ->assertForbidden();

        $this->assertDatabaseHas('pulse_presets', [
            'id' => $preset->id,
            'name' => 'Existing Preset',
        ]);

        $this->assertDatabaseMissing('pulse_presets', [
            'name' => 'New Preset',
        ]);
    }

    public function test_active_trial_can_create_and_list_server_presets(): void
    {
        $user = $this->createActiveTrialUser();

        $this
            ->actingAs($user)
            ->postJson(
                route('pulse-presets.store'),
                $this->validPresetPayload()
            )
            ->assertCreated();

        $this
            ->actingAs($user)
            ->getJson(route('pulse-presets.index'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Three Pulse');
    }

    public function test_pro_can_create_and_list_server_presets(): void
    {
        $user = $this->createProUser();

        $this
            ->actingAs($user)
            ->postJson(
                route('pulse-presets.store'),
                $this->validPresetPayload()
            )
            ->assertCreated();

        $this
            ->actingAs($user)
            ->getJson(route('pulse-presets.index'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Three Pulse');
    }

    public function test_user_cannot_update_or_delete_another_users_preset(): void
    {
        $owner = $this->createProUser();
        $otherUser = $this->createProUser();

        $preset = $this->createPresetFor($owner);

        $this
            ->actingAs($otherUser)
            ->patchJson(
                route('pulse-presets.update', $preset),
                [
                    'name' => 'Stolen Preset',
                ]
            )
            ->assertForbidden();

        $this
            ->actingAs($otherUser)
            ->deleteJson(
                route('pulse-presets.destroy', $preset)
            )
            ->assertForbidden();

        $this->assertDatabaseHas('pulse_presets', [
            'id' => $preset->id,
            'user_id' => $owner->id,
            'name' => 'Existing Preset',
        ]);
    }
}
