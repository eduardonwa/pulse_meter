<?php

namespace Tests\Feature\Email;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_cannot_access_plans(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('billing.index'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_access_plans(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('billing.index'));

        $response->assertOk();
    }

    public function test_unverified_user_can_see_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('verification.notice'));

        $response
            ->assertOk()
            ->assertSeeText('Verify your email')
            ->assertSeeText('Resend email');
    }
}