<?php

namespace Tests\Feature\Billing;

use App\Listeners\DowngradeProFromEndedStripeSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Events\WebhookHandled;
use Tests\TestCase;

class DowngradeProFromEndedStripeSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_ended_monthly_subscription_downgrades_the_user(): void
    {
        config()->set(
            'billing.pro.monthly_price_id',
            'price_dorelog_monthly'
        );

        $user = User::factory()->create([
            'plan' => 'pro',
        ]);

        $user->forceFill([
            'stripe_id' => 'cus_dorelog_user',
        ])->save();

        app(
            DowngradeProFromEndedStripeSubscription::class
        )->handle(
            new WebhookHandled([
                'type' => 'customer.subscription.deleted',
                'data' => [
                    'object' => [
                        'customer' => 'cus_dorelog_user',
                        'items' => [
                            'data' => [
                                [
                                    'price' => [
                                        'id' =>
                                            'price_dorelog_monthly',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
        );

        $this->assertSame(
            'free',
            $user->refresh()->plan
        );
    }

    public function test_it_ignores_an_unrelated_ended_subscription(): void
    {
        config()->set(
            'billing.pro.monthly_price_id',
            'price_dorelog_monthly'
        );

        $user = User::factory()->create([
            'plan' => 'pro',
        ]);

        $user->forceFill([
            'stripe_id' => 'cus_dorelog_user',
        ])->save();

        app(
            DowngradeProFromEndedStripeSubscription::class
        )->handle(
            new WebhookHandled([
                'type' => 'customer.subscription.deleted',
                'data' => [
                    'object' => [
                        'customer' => 'cus_dorelog_user',
                        'items' => [
                            'data' => [
                                [
                                    'price' => [
                                        'id' =>
                                            'price_another_product',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
        );

        $this->assertSame(
            'pro',
            $user->refresh()->plan
        );
    }

    public function test_it_preserves_pro_when_a_replacement_subscription_is_active(): void
    {
        config()->set(
            'billing.pro.monthly_price_id',
            'price_dorelog_monthly'
        );

        $user = User::factory()->create([
            'plan' => 'pro',
        ]);

        $user->forceFill([
            'stripe_id' => 'cus_dorelog_user',
        ])->save();

        $user->subscriptions()->create([
            'type' => 'pro',
            'stripe_id' => 'sub_ended',
            'stripe_status' => 'canceled',
            'stripe_price' => 'price_dorelog_monthly',
            'quantity' => 1,
            'ends_at' => now()->subSecond(),
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $user->subscriptions()->create([
            'type' => 'pro',
            'stripe_id' => 'sub_replacement',
            'stripe_status' => 'active',
            'stripe_price' => 'price_dorelog_monthly',
            'quantity' => 1,
        ]);

        app(
            DowngradeProFromEndedStripeSubscription::class
        )->handle(
            new WebhookHandled([
                'type' => 'customer.subscription.deleted',
                'data' => [
                    'object' => [
                        'id' => 'sub_ended',
                        'customer' => 'cus_dorelog_user',

                        'items' => [
                            'data' => [
                                [
                                    'price' => [
                                        'id' =>
                                            'price_dorelog_monthly',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
        );

        $this->assertSame(
            'pro',
            $user->refresh()->plan
        );
    }
}
