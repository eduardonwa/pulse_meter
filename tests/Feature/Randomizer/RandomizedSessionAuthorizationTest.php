<?php

namespace Tests\Feature\Randomizer;

use App\Models\RandomizedSession;
use App\Models\TrialEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RandomizedSessionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(
        string $name = 'B Lydian in 7/8'
    ): array {
        return [
            'name' => $name,
            'bpm' => 118,
            'root' => 'B',
            'scale' => 'lydian',
            'playback_mode' => 'pulse',
            'numerator' => 7,
            'denominator' => 8,
            'subdivision' => 1,
            'grouping' => [2, 2, 3],
            'pattern' => [
                ['sound' => 'accent', 'groupStart' => true],
                ['sound' => 'click', 'groupStart' => false],
                ['sound' => 'accent', 'groupStart' => true],
                ['sound' => 'click', 'groupStart' => false],
                ['sound' => 'accent', 'groupStart' => true],
                ['sound' => 'click', 'groupStart' => false],
                ['sound' => 'click', 'groupStart' => false],
            ],
        ];
    }

    private function proUser(): User
    {
        return User::factory()->create(['plan' => 'pro']);
    }

    private function trialUser(): User
    {
        $user = User::factory()->create(['plan' => 'free']);

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

    private function createFor(User $user): RandomizedSession
    {
        return $user->randomizedSessions()->create([
            ...$this->validPayload(),
            'position' => 1,
        ]);
    }

    public function test_pro_can_create_list_update_and_delete(): void
    {
        $user = $this->proUser();

        $created = $this
            ->actingAs($user)
            ->postJson(
                route('randomized-sessions.store'),
                $this->validPayload()
            )
            ->assertCreated()
            ->assertJsonPath('root', 'B')
            ->assertJsonPath('scale', 'lydian')
            ->json();

        $this
            ->actingAs($user)
            ->getJson(route('randomized-sessions.index'))
            ->assertOk()
            ->assertJsonCount(1);

        $this
            ->actingAs($user)
            ->patchJson(
                route('randomized-sessions.update', $created['id']),
                ['name' => 'Renamed']
            )
            ->assertOk()
            ->assertJsonPath('name', 'Renamed');

        $this
            ->actingAs($user)
            ->deleteJson(
                route('randomized-sessions.destroy', $created['id'])
            )
            ->assertOk();
    }

    public function test_trial_can_store_sessions(): void
    {
        $this
            ->actingAs($this->trialUser())
            ->postJson(
                route('randomized-sessions.store'),
                $this->validPayload()
            )
            ->assertCreated();
    }

    public function test_free_user_cannot_access_routes(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        $session = $this->createFor($user);

        $this
            ->actingAs($user)
            ->getJson(route('randomized-sessions.index'))
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->deleteJson(
                route('randomized-sessions.destroy', $session)
            )
            ->assertForbidden();
    }

    public function test_user_cannot_change_another_users_session(): void
    {
        $owner = $this->proUser();
        $other = $this->proUser();
        $session = $this->createFor($owner);

        $this
            ->actingAs($other)
            ->patchJson(
                route('randomized-sessions.update', $session),
                ['name' => 'Stolen']
            )
            ->assertForbidden();

        $this
            ->actingAs($other)
            ->deleteJson(
                route('randomized-sessions.destroy', $session)
            )
            ->assertForbidden();
    }
}
