<?php

namespace Tests\Feature\Trial;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivateTrialTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_free_user_can_activate_trial(): void
    {
        $now = CarbonImmutable::parse('2026-08-04 14:00:00');

        $this->travelTo($now);

        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->post(route('trial.activate'));

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'trial_status',
                'Trial Mode enabled. You have 60 minutes of Pro access.',
            );

        $this->assertDatabaseHas('trial_entitlements', [
            'user_id' => $user->id,
            'status' => 'active',
            'granted_seconds' => 3600,
            'used_seconds' => 0,
        ]);

        $trial = $user
            ->trialEntitlement()
            ->firstOrFail();

        $this->assertTrue(
            $trial->started_at->equalTo($now)
        );

        $this->assertTrue(
            $trial->expires_at->equalTo(
                $now->addDays(15)
            )
        );

        $this->assertNull($trial->active_session_id);
        $this->assertNull($trial->last_heartbeat_at);
    }

    public function test_trial_cannot_be_activated_twice(): void
    {
        $now = CarbonImmutable::parse('2026-08-04 14:00:00');

        $this->travelTo($now);

        $user = $this->createUser();

        $this
            ->actingAs($user)
            ->post(route('trial.activate'))
            ->assertSessionHas('trial_status');

        $trial = $user
            ->trialEntitlement()
            ->firstOrFail();

        $originalStartedAt = $trial->started_at;
        $originalExpiresAt = $trial->expires_at;

        $this->travelTo($now->addHour());

        $response = $this
            ->actingAs($user)
            ->post(route('trial.activate'));

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'trial_error',
                'This account has already used its Pro trial.',
            );

        $trial->refresh();

        $this->assertTrue(
            $trial->started_at->equalTo($originalStartedAt)
        );

        $this->assertTrue(
            $trial->expires_at->equalTo($originalExpiresAt)
        );

        $this->assertSame(1, $user
            ->trialEntitlement()
            ->count());
    }

    public function test_completed_trial_cannot_be_restarted(): void
    {
        $user = $this->createUser();

        $trial = $user->trialEntitlement()->create([
            'status' => 'completed',
            'granted_seconds' => 3600,
            'used_seconds' => 3600,
            'started_at' => now()->subDays(5),
            'expires_at' => now()->addDays(10),
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('trial.activate'));

        $response->assertSessionHas(
            'trial_error',
            'This account has already used its Pro trial.',
        );

        $trial->refresh();

        $this->assertSame('completed', $trial->status);
        $this->assertSame(3600, $trial->used_seconds);
    }

    public function test_pro_user_cannot_activate_trial(): void
    {
        $user = $this->createUser([
            'plan' => 'pro',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('trial.activate'));

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'trial_error',
                'Your account already has Pro access.',
            );

        $this->assertDatabaseMissing('trial_entitlements', [
            'user_id' => $user->id,
        ]);
    }

    public function test_unverified_user_cannot_activate_trial(): void
    {
        /** @var User $user */
        $user = User::factory()
            ->unverified()
            ->create();

        $response = $this
            ->actingAs($user)
            ->post(route('trial.activate'));

        $response->assertRedirect();

        $this->assertDatabaseMissing('trial_entitlements', [
            'user_id' => $user->id,
        ]);
    }

    private function createUser(
        array $attributes = [],
    ): User {
        /** @var User $user */
        $user = User::factory()->create($attributes);

        return $user;
    }
}