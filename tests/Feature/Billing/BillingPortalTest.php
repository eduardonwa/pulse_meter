<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use App\Services\Billing\OpenBillingPortal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class BillingPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_billing_portal(): void
    {
        $this->post(route('billing.portal'))
            ->assertRedirect(route('login'));
    }

    public function test_free_user_cannot_open_billing_portal(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $this->actingAs($user)
            ->post(route('billing.portal'))
            ->assertStatus(409);
    }

    public function test_monthly_user_is_redirected_to_billing_portal(): void
    {
        $user = User::factory()->create([
            'plan' => 'pro',
        ]);

        $user->subscriptions()->create([
            'type' => 'pro',
            'stripe_id' => 'sub_billing_portal',
            'stripe_status' => 'active',
            'stripe_price' => 'price_monthly_pro',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $this->mock(
            OpenBillingPortal::class,
            function (MockInterface $mock) use ($user): void {
                $mock
                    ->shouldReceive('open')
                    ->once()
                    ->withArgs(
                        fn (User $receivedUser): bool =>
                            $receivedUser->is($user)
                    )
                    ->andReturn(
                        redirect()->away(
                            'https://billing.stripe.test/session'
                        )
                    );
            }
        );

        $this->actingAs($user)
            ->post(route('billing.portal'))
            ->assertRedirect(
                'https://billing.stripe.test/session'
            );
    }
}