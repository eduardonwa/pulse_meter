<?php

namespace Tests\Feature\Billing;

use App\Listeners\GrantLifetimeProFromStripeCheckout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Events\WebhookReceived;
use Tests\TestCase;

class GrantLifetimeProFromStripeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_paid_lifetime_checkout_grants_pro(): void
    {
        config()->set(
            'billing.pro.lifetime_price_id',
            'price_dorelog_lifetime'
        );

        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $user->forceFill([
            'stripe_id' => 'cus_lifetime_user',
        ])->save();

        app(
            GrantLifetimeProFromStripeCheckout::class
        )->handle(
            new WebhookReceived([
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_lifetime_paid',
                        'mode' => 'payment',
                        'payment_status' => 'paid',
                        'customer' => 'cus_lifetime_user',
                        'payment_intent' =>
                            'pi_lifetime_paid',
                        'metadata' => [
                            'dorelog_user_id' =>
                                (string) $user->getKey(),
                            'dorelog_purchase' =>
                                'pro_lifetime',
                            'dorelog_price_id' =>
                                'price_dorelog_lifetime',
                        ],
                    ],
                ],
            ])
        );

        $this->assertSame(
            'pro',
            $user->refresh()->plan
        );

        $this->assertTrue(
            $user->hasLifetimePro()
        );

        $this->assertSame(
            1,
            $user->lifetimePurchases()->count()
        );

        $this->assertSame(
            'cs_lifetime_paid',
            $user
                ->lifetimeEntitlement
                ->stripe_checkout_session_id
        );
    }

    public function test_an_unpaid_checkout_does_not_grant_lifetime(): void
    {
        config()->set(
            'billing.pro.lifetime_price_id',
            'price_dorelog_lifetime'
        );

        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $user->forceFill([
            'stripe_id' => 'cus_unpaid_lifetime',
        ])->save();

        app(
            GrantLifetimeProFromStripeCheckout::class
        )->handle(
            new WebhookReceived([
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_lifetime_unpaid',
                        'mode' => 'payment',
                        'payment_status' => 'unpaid',
                        'customer' =>
                            'cus_unpaid_lifetime',
                        'payment_intent' => null,
                        'metadata' => [
                            'dorelog_user_id' =>
                                (string) $user->getKey(),
                            'dorelog_purchase' =>
                                'pro_lifetime',
                            'dorelog_price_id' =>
                                'price_dorelog_lifetime',
                        ],
                    ],
                ],
            ])
        );

        $this->assertSame(
            'free',
            $user->refresh()->plan
        );

        $this->assertFalse(
            $user->hasLifetimePro()
        );

        $this->assertSame(
            0,
            $user->lifetimePurchases()->count()
        );
    }

    public function test_a_checkout_for_another_price_is_ignored(): void
    {
        config()->set(
            'billing.pro.lifetime_price_id',
            'price_dorelog_lifetime'
        );

        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $user->forceFill([
            'stripe_id' => 'cus_wrong_price',
        ])->save();

        app(
            GrantLifetimeProFromStripeCheckout::class
        )->handle(
            new WebhookReceived([
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_wrong_price',
                        'mode' => 'payment',
                        'payment_status' => 'paid',
                        'customer' => 'cus_wrong_price',
                        'payment_intent' =>
                            'pi_wrong_price',
                        'metadata' => [
                            'dorelog_user_id' =>
                                (string) $user->getKey(),
                            'dorelog_purchase' =>
                                'pro_lifetime',
                            'dorelog_price_id' =>
                                'price_another_product',
                        ],
                    ],
                ],
            ])
        );

        $this->assertSame(
            'free',
            $user->refresh()->plan
        );

        $this->assertFalse(
            $user->hasLifetimePro()
        );
    }

    public function test_a_mismatched_stripe_customer_is_ignored(): void
    {
        config()->set(
            'billing.pro.lifetime_price_id',
            'price_dorelog_lifetime'
        );

        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $user->forceFill([
            'stripe_id' => 'cus_real_user',
        ])->save();

        app(
            GrantLifetimeProFromStripeCheckout::class
        )->handle(
            new WebhookReceived([
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_wrong_customer',
                        'mode' => 'payment',
                        'payment_status' => 'paid',
                        'customer' => 'cus_another_user',
                        'payment_intent' =>
                            'pi_wrong_customer',
                        'metadata' => [
                            'dorelog_user_id' =>
                                (string) $user->getKey(),
                            'dorelog_purchase' =>
                                'pro_lifetime',
                            'dorelog_price_id' =>
                                'price_dorelog_lifetime',
                        ],
                    ],
                ],
            ])
        );

        $this->assertSame(
            'free',
            $user->refresh()->plan
        );

        $this->assertFalse(
            $user->hasLifetimePro()
        );
    }
}
