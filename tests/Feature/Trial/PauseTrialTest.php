<?php

namespace Tests\Feature\Trial;

use App\Models\TrialEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PauseTrialTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create();

        return $user;
    }

    public function test_session_owner_can_pause_trial(): void
    {
        $user = $this->createUser();

        $sessionId = (string) Str::uuid();

        $trial = $this->createActiveTrial(
            user: $user,
            sessionId: $sessionId,
        );

        $response = $this
            ->actingAs($user)
            ->post(route('trial.pause'), [
                'session_id' => $sessionId,
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'trial_status',
                'Trial Mode paused.',
            );

        $trial->refresh();

        $this->assertSame('paused', $trial->status);
        $this->assertSame('manual', $trial->pause_reason);
        $this->assertNull($trial->active_session_id);
    }

    public function test_different_session_cannot_pause_owned_trial(): void
    {
        $user = $this->createUser();

        $ownerSessionId = (string) Str::uuid();
        $foreignSessionId = (string) Str::uuid();

        $trial = $this->createActiveTrial(
            user: $user,
            sessionId: $ownerSessionId,
        );

        $response = $this
            ->actingAs($user)
            ->post(route('trial.pause'), [
                'session_id' => $foreignSessionId,
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'trial_error',
                'Trial Mode is active in another tab.',
            );

        $trial->refresh();

        $this->assertSame('active', $trial->status);
        $this->assertSame(
            $ownerSessionId,
            $trial->active_session_id,
        );

        $this->assertNull($trial->paused_at);
        $this->assertNull($trial->pause_reason);
    }

    public function test_pause_requires_a_valid_session_id(): void
    {
        $user = $this->createUser();

        $this->createActiveTrial(
            user: $user,
            sessionId: (string) Str::uuid(),
        );

        $response = $this
            ->actingAs($user)
            ->post(route('trial.pause'), [
                'session_id' => 'not-a-uuid',
            ]);

        $response->assertSessionHasErrors('session_id');
    }

    private function createActiveTrial(
        User $user,
        string $sessionId,
    ): TrialEntitlement {
        $trial = new TrialEntitlement();

        $trial->status = 'active';
        $trial->granted_seconds = 3600;
        $trial->used_seconds = 100;
        $trial->started_at = now()->subMinutes(5);
        $trial->expires_at = now()->addDays(15);
        $trial->paused_at = null;
        $trial->pause_reason = null;
        $trial->active_session_id = $sessionId;
        $trial->last_heartbeat_at = now()->subSeconds(5);
        $trial->completed_at = null;

        $user->trialEntitlement()->save($trial);

        return $trial;
    }
}