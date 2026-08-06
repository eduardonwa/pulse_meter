<?php

namespace Tests\Feature\Billing;

use App\Listeners\ActivateProFromStripeSubscription;
use App\Models\TrialEntitlement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Cashier\Events\WebhookHandled;
use Tests\TestCase;

class ActivateProFromStripeSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_active_monthly_subscription_upgrades_the_user(): void
    {
        config()->set(
            'billing.pro.monthly_price_id',
            'price_dorelog_monthly'
        );

        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $user->forceFill([
            'stripe_id' => 'cus_dorelog_user',
        ])->save();

        $trial = new TrialEntitlement();

        $trial->forceFill([
            'status' => 'active',
            'granted_seconds' => 3600,
            'used_seconds' => 49,
            'started_at' => now()->subMinute(),
            'expires_at' => now()->addDays(30),
            'active_session_id' => (string) Str::uuid(),
            'last_heartbeat_at' => now(),
        ]);

        $trial->user()->associate($user);
        $trial->save();

        $periodEnd = now()
            ->addMonth()
            ->startOfSecond()
            ->timestamp;

        $localSubscription = $user->subscriptions()->create([
            'type' => 'pro',
            'stripe_id' => 'sub_dorelog_monthly',
            'stripe_status' => 'active',
            'stripe_price' => 'price_dorelog_monthly',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        app(
            ActivateProFromStripeSubscription::class
        )->handle(
            new WebhookHandled([
                'type' => 'customer.subscription.created',
                'data' => [
                    'object' => [
                        'id' => 'sub_dorelog_monthly',
                        'customer' => 'cus_dorelog_user',
                        'status' => 'active',
                        'items' => [
                            'data' => [
                                [
                                    'price' => [
                                        'id' => 'price_another_product',
                                    ],
                                    'current_period_end' =>
                                        now()->addDays(5)->timestamp,
                                ],
                                [
                                    'price' => [
                                        'id' =>
                                            'price_dorelog_monthly',
                                    ],
                                    'current_period_end' =>
                                        $periodEnd,
                                ],
                            ],
                        ],
                    ],
                ],
            ])
        );

        $user->refresh();
        $trial->refresh();

        $this->assertSame('pro', $user->plan);
        $this->assertSame('converted', $trial->status);
        $this->assertNotNull($trial->converted_at);

        $storedPeriodEnd = $localSubscription
            ->fresh()
            ->getRawOriginal('current_period_ends_at');

        $this->assertNotNull($storedPeriodEnd);

        $this->assertSame(
            $periodEnd,
            Carbon::parse(
                $storedPeriodEnd,
                'UTC'
            )->timestamp
        );
    }

    public function test_it_ignores_an_inactive_or_unrelated_subscription(): void
    {
        config()->set(
            'billing.pro.monthly_price_id',
            'price_dorelog_monthly'
        );

        foreach (
            [
                [
                    'status' => 'past_due',
                    'price' => 'price_dorelog_monthly',
                ],
                [
                    'status' => 'active',
                    'price' => 'price_another_product',
                ],
            ] as $index => $scenario
        ) {
            $user = User::factory()->create([
                'plan' => 'free',
            ]);

            $customerId = "cus_ignored_{$index}";

            $user->forceFill([
                'stripe_id' => $customerId,
            ])->save();

            app(
                ActivateProFromStripeSubscription::class
            )->handle(
                new WebhookHandled([
                    'type' => 'customer.subscription.updated',
                    'data' => [
                        'object' => [
                            'customer' => $customerId,
                            'status' => $scenario['status'],
                            'items' => [
                                'data' => [
                                    [
                                        'price' => [
                                            'id' =>
                                                $scenario['price'],
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
    }
}
