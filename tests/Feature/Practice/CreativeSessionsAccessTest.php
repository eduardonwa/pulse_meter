<?php

namespace Tests\Feature\Practice;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreativeSessionsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_creative_sessions_are_disabled_by_default(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);

        $this->assertFalse($user->canUseCreativeSessions());

        $this->actingAs($user)
            ->getJson('/randomized-sessions')
            ->assertForbidden();
    }

    public function test_a_pro_user_can_access_enabled_experimental_sessions(): void
    {
        $user = User::factory()->create([
            'plan' => 'pro',
            'creative_sessions_enabled' => true,
        ]);

        $this->assertTrue($user->canUseCreativeSessions());

        $this->actingAs($user)
            ->getJson('/randomized-sessions')
            ->assertOk();
    }

    public function test_an_expired_or_ineligible_account_cannot_use_experiment_with_flag_alone(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
            'creative_sessions_enabled' => true,
        ]);

        $this->assertFalse($user->canUseCreativeSessions());
    }
}
