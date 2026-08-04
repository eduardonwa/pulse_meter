<?php

namespace Tests\Feature\Trial;

use App\Models\TrialEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResumeTrialTest extends TestCase
{
    use RefreshDatabase;

    public function test_paused_trial_can_be_resumed_by_new_session(): void
    {
        $user = $this->createUser();
        $sessionId = (string) Str::uuid();

        $trial = $this->createTrial($user, [
            'status' => 'paused',
            'paused_at' => now()->subMinute(),
            'pause_reason' => 'manual',
            'active_session_id' => null,
            'last_heartbeat_at' => now()->subMinute(),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('trial.resume'), [
                'session_id' => $sessionId,
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'trial_status',
                'Trial Mode resumed.',
            );

        $trial->refresh();

        $this->assertSame('active', $trial->status);
        $this->assertSame(
            $sessionId,
            $trial->active_session_id,
        );

        $this->assertNull($trial->paused_at);
        $this->assertNull($trial->pause_reason);
        $this->assertNotNull($trial->last_heartbeat_at);
    }

    public function test_resume_requires_valid_session_id(): void
    {
        $user = $this->createUser();

        $this->createTrial($user, [
            'status' => 'paused',
            'paused_at' => now()->subMinute(),
            'pause_reason' => 'manual',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('trial.resume'), [
                'session_id' => 'invalid',
            ]);

        $response->assertSessionHasErrors('session_id');
    }

    public function test_active_trial_cannot_be_resumed(): void
    {
        $user = $this->createUser();
        $ownerSessionId = (string) Str::uuid();

        $trial = $this->createTrial($user, [
            'status' => 'active',
            'active_session_id' => $ownerSessionId,
            'last_heartbeat_at' => now()->subSeconds(5),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('trial.resume'), [
                'session_id' => (string) Str::uuid(),
            ]);

        $response->assertSessionHas(
            'trial_error',
            'Trial Mode cannot be resumed.',
        );

        $trial->refresh();

        $this->assertSame('active', $trial->status);
        $this->assertSame(
            $ownerSessionId,
            $trial->active_session_id,
        );
    }

    public function test_expired_trial_cannot_be_resumed(): void
    {
        $user = $this->createUser();

        $trial = $this->createTrial($user, [
            'status' => 'paused',
            'expires_at' => now()->subSecond(),
            'paused_at' => now()->subMinute(),
            'pause_reason' => 'manual',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('trial.resume'), [
                'session_id' => (string) Str::uuid(),
            ]);

        $response->assertSessionHas(
            'trial_error',
            'Your Trial Mode has expired.',
        );

        $trial->refresh();

        $this->assertSame('expired', $trial->status);
        $this->assertNull($trial->active_session_id);
    }

    public function test_consumed_trial_cannot_be_resumed(): void
    {
        $user = $this->createUser();

        $trial = $this->createTrial($user, [
            'status' => 'paused',
            'used_seconds' => 3600,
            'paused_at' => now()->subMinute(),
            'pause_reason' => 'manual',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('trial.resume'), [
                'session_id' => (string) Str::uuid(),
            ]);

        $response->assertSessionHas(
            'trial_error',
            'Your Trial Mode is complete.',
        );

        $trial->refresh();

        $this->assertSame('completed', $trial->status);
        $this->assertSame(3600, $trial->used_seconds);
        $this->assertNotNull($trial->completed_at);
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