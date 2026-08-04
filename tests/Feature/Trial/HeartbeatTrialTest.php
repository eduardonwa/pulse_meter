<?php

namespace Tests\Feature\Trial;

use App\Models\TrialEntitlement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HeartbeatTrialTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_heartbeat_claims_unowned_trial(): void
    {
        $now = CarbonImmutable::parse('2026-08-04 14:00:00');

        $this->travelTo($now);

        $user = $this->createUser();
        $sessionId = (string) Str::uuid();

        $trial = $this->createTrial($user, [
            'active_session_id' => null,
            'last_heartbeat_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('trial.heartbeat'), [
                'event' => 'heartbeat',
                'session_id' => $sessionId,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'active',
                'remaining_seconds' => 3500,
            ]);

        $trial->refresh();

        $this->assertSame(
            $sessionId,
            $trial->active_session_id,
        );

        $this->assertTrue(
            $trial->last_heartbeat_at->equalTo($now)
        );
    }

    public function test_owner_heartbeat_bills_elapsed_active_time(): void
    {
        $now = CarbonImmutable::parse('2026-08-04 14:00:00');

        $this->travelTo($now);

        $user = $this->createUser();
        $sessionId = (string) Str::uuid();

        $trial = $this->createTrial($user, [
            'used_seconds' => 100,
            'active_session_id' => $sessionId,
            'last_heartbeat_at' => $now->subSeconds(10),
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('trial.heartbeat'), [
                'event' => 'heartbeat',
                'session_id' => $sessionId,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'active',
                'remaining_seconds' => 3490,
            ]);

        $trial->refresh();

        $this->assertSame(110, $trial->used_seconds);
        $this->assertSame('active', $trial->status);
    }

    public function test_different_session_is_blocked_while_owner_is_fresh(): void
    {
        $now = CarbonImmutable::parse('2026-08-04 14:00:00');

        $this->travelTo($now);

        $user = $this->createUser();

        $ownerSessionId = (string) Str::uuid();
        $foreignSessionId = (string) Str::uuid();

        $trial = $this->createTrial($user, [
            'active_session_id' => $ownerSessionId,
            'last_heartbeat_at' => $now->subSeconds(10),
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('trial.heartbeat'), [
                'event' => 'heartbeat',
                'session_id' => $foreignSessionId,
            ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'status' => 'active_elsewhere',
            ]);

        $trial->refresh();

        $this->assertSame('active', $trial->status);

        $this->assertSame(
            $ownerSessionId,
            $trial->active_session_id,
        );
    }

    public function test_late_heartbeat_from_same_session_pauses_trial(): void
    {
        $now = CarbonImmutable::parse('2026-08-04 14:00:00');

        $this->travelTo($now);

        $user = $this->createUser();
        $sessionId = (string) Str::uuid();

        $trial = $this->createTrial($user, [
            'used_seconds' => 100,
            'active_session_id' => $sessionId,
            'last_heartbeat_at' => $now->subSeconds(21),
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('trial.heartbeat'), [
                'event' => 'heartbeat',
                'session_id' => $sessionId,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'paused',
                'remaining_seconds' => 3480,
            ]);

        $trial->refresh();

        $this->assertSame('paused', $trial->status);
        $this->assertSame('inactivity', $trial->pause_reason);
        $this->assertSame(120, $trial->used_seconds);
        $this->assertNull($trial->active_session_id);
    }

    private function createUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create();

        return $user;
    }

    private function createTrial(
        User $user,
        array $overrides = [],
    ): TrialEntitlement {
        $trial = new TrialEntitlement();

        $attributes = array_merge([
            'status' => 'active',
            'granted_seconds' => 3600,
            'used_seconds' => 100,
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addDays(15),
            'paused_at' => null,
            'pause_reason' => null,
            'active_session_id' => null,
            'last_heartbeat_at' => null,
            'completed_at' => null,
        ], $overrides);

        foreach ($attributes as $attribute => $value) {
            $trial->{$attribute} = $value;
        }

        $user->trialEntitlement()->save($trial);

        return $trial;
    }
}