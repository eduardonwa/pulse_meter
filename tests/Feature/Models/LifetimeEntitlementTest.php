<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifetimeEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_have_active_lifetime_pro_access(): void
    {
        $user = User::factory()->create([
            'plan' => 'pro',
        ]);

        $entitlement = $user
            ->lifetimeEntitlement()
            ->create([
                'stripe_checkout_session_id' =>
                    'cs_lifetime_purchase',
                'stripe_payment_intent_id' =>
                    'pi_lifetime_purchase',
                'stripe_price_id' =>
                    'price_dorelog_lifetime',
                'granted_at' => now(),
            ]);

        $this->assertTrue($entitlement->isActive());
        $this->assertTrue($user->hasLifetimePro());
        $this->assertTrue(
            $user->is(
                $entitlement->user
            )
        );
    }

    public function test_a_revoked_lifetime_entitlement_is_not_active(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $entitlement = $user
            ->lifetimeEntitlement()
            ->create([
                'stripe_checkout_session_id' =>
                    'cs_revoked_lifetime',
                'stripe_payment_intent_id' =>
                    'pi_revoked_lifetime',
                'stripe_price_id' =>
                    'price_dorelog_lifetime',
                'granted_at' => now()->subDay(),
                'revoked_at' => now(),
            ]);

        $this->assertFalse($entitlement->isActive());
        $this->assertFalse($user->hasLifetimePro());
    }
}
