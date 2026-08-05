<?php

namespace Tests\Feature\Plans;

use App\Models\User;
use App\Services\Plans\GrantLifetimePro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\Plans\DowngradeToFree;

class GrantLifetimeProTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_grants_lifetime_and_upgrades_the_user(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $entitlement = app(
            GrantLifetimePro::class
        )->grant(
            $user,
            'cs_lifetime_purchase',
            'pi_lifetime_purchase',
            'price_dorelog_lifetime'
        );

        $this->assertSame(
            'pro',
            $user->refresh()->plan
        );

        $this->assertTrue($entitlement->isActive());
        $this->assertTrue($user->hasLifetimePro());

        $this->assertSame(
            'cs_lifetime_purchase',
            $entitlement->stripe_checkout_session_id
        );

        $this->assertSame(
            1,
            $user->lifetimeEntitlement()->count()
        );
    }

    public function test_repeating_the_same_checkout_is_idempotent(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $service = app(GrantLifetimePro::class);

        $first = $service->grant(
            $user,
            'cs_repeated_lifetime',
            'pi_repeated_lifetime',
            'price_dorelog_lifetime'
        );

        $grantedAt = $first->granted_at;

        $second = $service->grant(
            $user,
            'cs_repeated_lifetime',
            'pi_repeated_lifetime',
            'price_dorelog_lifetime'
        );

        $this->assertSame(
            1,
            $user->lifetimePurchases()->count()
        );

        $this->assertSame(
            1,
            $user->lifetimeEntitlement()->count()
        );

        $this->assertTrue(
            $second->granted_at->equalTo($grantedAt)
        );

        $this->assertSame(
            'pro',
            $user->refresh()->plan
        );
    }

    public function test_replaying_a_revoked_checkout_does_not_restore_access(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $service = app(GrantLifetimePro::class);

        $service->grant(
            $user,
            'cs_revoked_purchase',
            'pi_revoked_purchase',
            'price_dorelog_lifetime'
        );

        $user->lifetimeEntitlement()->update([
            'revoked_at' => now(),
        ]);

        app(DowngradeToFree::class)->downgrade($user);

        $this->assertSame(
            'free',
            $user->refresh()->plan,
            'The user was not downgraded before replaying Checkout.'
        );

        $this->assertSame(
            1,
            $user->lifetimePurchases()->count(),
            'The original Lifetime purchase was not recorded.'
        );

        $entitlement = $service->grant(
            $user,
            'cs_revoked_purchase',
            'pi_revoked_purchase',
            'price_dorelog_lifetime'
        );

        $this->assertFalse($entitlement->isActive());

        $this->assertSame(
            'free',
            $user->refresh()->plan
        );

        $this->assertSame(
            1,
            $user->lifetimePurchases()->count()
        );
    }

    public function test_a_new_checkout_reactivates_a_revoked_lifetime(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $user->lifetimeEntitlement()->create([
            'stripe_checkout_session_id' =>
                'cs_old_lifetime',
            'stripe_payment_intent_id' =>
                'pi_old_lifetime',
            'stripe_price_id' =>
                'price_dorelog_lifetime',
            'granted_at' => now()->subMonth(),
            'revoked_at' => now()->subDay(),
        ]);

        $entitlement = app(
            GrantLifetimePro::class
        )->grant(
            $user,
            'cs_new_lifetime',
            'pi_new_lifetime',
            'price_dorelog_lifetime'
        );

        $this->assertTrue($entitlement->isActive());

        $this->assertSame(
            'cs_new_lifetime',
            $entitlement->stripe_checkout_session_id
        );

        $this->assertSame(
            'pi_new_lifetime',
            $entitlement->stripe_payment_intent_id
        );

        $this->assertSame(
            'pro',
            $user->refresh()->plan
        );

        $this->assertSame(
            1,
            $user->lifetimePurchases()->count()
        );
    }

    public function test_an_old_checkout_cannot_reactivate_access_after_a_later_purchase(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $service = app(GrantLifetimePro::class);

        $service->grant(
            $user,
            'cs_old_lifetime',
            'pi_old_lifetime',
            'price_dorelog_lifetime'
        );

        $user->lifetimeEntitlement()->update([
            'revoked_at' => now(),
        ]);

        app(DowngradeToFree::class)->downgrade($user);

        $service->grant(
            $user,
            'cs_new_lifetime',
            'pi_new_lifetime',
            'price_dorelog_lifetime'
        );

        $user->lifetimeEntitlement()->update([
            'revoked_at' => now(),
        ]);

        app(DowngradeToFree::class)->downgrade($user);

        $entitlement = $service->grant(
            $user,
            'cs_old_lifetime',
            'pi_old_lifetime',
            'price_dorelog_lifetime'
        );

        $this->assertFalse($entitlement->isActive());

        $this->assertSame(
            'free',
            $user->refresh()->plan
        );

        $this->assertSame(
            2,
            $user->lifetimePurchases()->count()
        );
    }
}
