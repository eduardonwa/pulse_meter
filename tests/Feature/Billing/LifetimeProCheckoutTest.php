<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use App\Services\Billing\StartLifetimeProCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LifetimeProCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function createProUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
                'plan' => 'pro',
        ]);
        
        return $user;
    }

    private function createFreeUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
                'plan' => 'free',
        ]);
        
        return $user;
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $response = $this->post(
            route('billing.pro.lifetime.checkout')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_an_authenticated_user_is_redirected_to_checkout(): void
    {
        $user = $this->createFreeUser();

        $this->mock(
            StartLifetimeProCheckout::class,
            function (MockInterface $mock) use ($user): void {
                $mock
                    ->shouldReceive('start')
                    ->once()
                    ->withArgs(
                        fn (User $receivedUser): bool =>
                            $receivedUser->is($user)
                    )
                    ->andReturn(
                        redirect()->away(
                            'https://checkout.stripe.test/lifetime'
                        )
                    );
            }
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('billing.pro.lifetime.checkout')
            );

        $response->assertRedirect(
            'https://checkout.stripe.test/lifetime'
        );
    }

    public function test_an_existing_lifetime_user_cannot_checkout_again(): void
    {
        $user = $this->createProUser();

        $user->lifetimeEntitlement()->create([
            'stripe_checkout_session_id' =>
                'cs_existing_lifetime',
            'stripe_payment_intent_id' =>
                'pi_existing_lifetime',
            'stripe_price_id' =>
                'price_dorelog_lifetime',
            'granted_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('billing.pro.lifetime.checkout')
            );

        $response->assertStatus(
            Response::HTTP_CONFLICT
        );
    }

    public function test_a_monthly_pro_user_is_not_rejected_as_lifetime(): void
    {
        $user = $this->createProUser();

        $this->mock(
            StartLifetimeProCheckout::class,
            function (MockInterface $mock) use ($user): void {
                $mock
                    ->shouldReceive('start')
                    ->once()
                    ->withArgs(
                        fn (User $receivedUser): bool =>
                            $receivedUser->is($user)
                    )
                    ->andReturn(
                        redirect()->away(
                            'https://checkout.stripe.test/lifetime'
                        )
                    );
            }
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('billing.pro.lifetime.checkout')
            );

        $response->assertRedirect(
            'https://checkout.stripe.test/lifetime'
        );
    }
}
