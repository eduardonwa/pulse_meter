<?php

namespace Tests\Feature\Trial;

use App\Models\TrialEntitlement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_active_trial_is_authorized(): void
    {
        $now = CarbonImmutable::parse('2026-08-04 13:00:00');

        $this->travelTo($now);

        $user = User::factory()->create();

        $this->createTrial($user, [
            'active_session_id' => (string) Str::uuid(),
            'last_heartbeat_at' => $now->subSeconds(10),
        ]);

        $this->assertTrue(
            Gate::forUser($user)->allows('use-pro')
        );
    }

    public function test_stale_active_trial_is_not_authorized(): void
    {
        $now = CarbonImmutable::parse('2026-08-04 13:00:00');

        $this->travelTo($now);

        $user = User::factory()->create();

        $trial = $this->createTrial($user, [
            'used_seconds' => 100,
            'active_session_id' => (string) Str::uuid(),
            'last_heartbeat_at' => $now->subSeconds(21),
        ]);

        $this->assertFalse(
            Gate::forUser($user)->allows('use-pro')
        );

        $trial->refresh();

        $this->assertSame('paused', $trial->status);
        $this->assertSame('inactivity', $trial->pause_reason);
        $this->assertNull($trial->active_session_id);
        $this->assertSame(120, $trial->used_seconds);
    }

    public function test_unclaimed_trial_is_denied_after_initial_grace(): void
    {
        $now = CarbonImmutable::parse('2026-08-04 13:00:00');

        $this->travelTo($now);

        $user = User::factory()->create();

        $trial = $this->createTrial($user, [
            'started_at' => $now->subSeconds(21),
            'active_session_id' => null,
            'last_heartbeat_at' => null,
        ]);

        $this->assertFalse(
            Gate::forUser($user)->allows('use-pro')
        );

        $trial->refresh();

        $this->assertSame('paused', $trial->status);
        $this->assertSame('inactivity', $trial->pause_reason);
        $this->assertNull($trial->active_session_id);
    }

    private function createTrial(
        User $user,
        array $overrides = [],
    ): TrialEntitlement {
        $trial = new TrialEntitlement();

        $attributes = array_merge([
            'status' => 'active',
            'granted_seconds' => 3600,
            'used_seconds' => 0,
            'started_at' => now(),
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

    public function test_pro_user_is_authorized_without_a_trial(): void
    {
        $user = User::factory()->create([
            'plan' => 'pro',
        ]);

        $this->assertTrue(
            Gate::forUser($user)->allows('use-pro')
        );
    }

    public function test_expired_trial_is_denied_and_normalized(): void
    {
        $now = CarbonImmutable::parse('2026-08-04 13:00:00');

        $this->travelTo($now);

        $user = User::factory()->create();

        $trial = $this->createTrial($user, [
            'expires_at' => $now->subSecond(),
            'active_session_id' => (string) Str::uuid(),
            'last_heartbeat_at' => $now->subSeconds(5),
        ]);

        $this->assertFalse(
            Gate::forUser($user)->allows('use-pro')
        );

        $trial->refresh();

        $this->assertSame('expired', $trial->status);
        $this->assertNull($trial->active_session_id);
    }

    public function test_fully_consumed_trial_is_denied_and_normalized(): void
    {
        $user = User::factory()->create();

        $trial = $this->createTrial($user, [
            'used_seconds' => 3600,
            'active_session_id' => (string) Str::uuid(),
            'last_heartbeat_at' => now()->subSeconds(5),
        ]);

        $this->assertFalse(
            Gate::forUser($user)->allows('use-pro')
        );

        $trial->refresh();

        $this->assertSame('completed', $trial->status);
        $this->assertSame(3600, $trial->used_seconds);
        $this->assertNotNull($trial->completed_at);
        $this->assertNull($trial->active_session_id);
    }

    public function test_paused_trial_is_denied_without_changing_its_state(): void
    {
        $user = User::factory()->create();

        $trial = $this->createTrial($user, [
            'status' => 'paused',
            'paused_at' => now()->subMinute(),
            'pause_reason' => 'manual',
            'active_session_id' => null,
            'last_heartbeat_at' => now()->subMinute(),
        ]);

        $this->assertFalse(
            Gate::forUser($user)->allows('use-pro')
        );

        $trial->refresh();

        $this->assertSame('paused', $trial->status);
        $this->assertSame('manual', $trial->pause_reason);
    }
}