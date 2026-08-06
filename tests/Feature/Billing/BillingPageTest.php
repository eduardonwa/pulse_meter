<?php

namespace Tests\Feature\Billing;

use App\Models\TrialEntitlement;
use App\Models\User;
use App\Services\Plans\GrantLifetimePro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingPageTest extends TestCase
{
    use RefreshDatabase;

    private function createFreeUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        return $user;
    }

    private function createProUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
            'plan' => 'pro',
        ]);

        return $user;
    }

    private function createMonthlyProUser(): User
    {
        $user = $this->createProUser();

        $user->subscriptions()->create([
            'type' => 'pro',
            'stripe_id' => 'sub_billing_page_monthly',
            'stripe_status' => 'active',
            'stripe_price' => 'price_billing_page_monthly',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        return $user->refresh();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('billing.index'))
            ->assertRedirect(route('login'));
    }

    public function test_free_user_sees_free_access(): void
    {
        $user = $this->createFreeUser();

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText('Plans & billing')
            ->assertSeeText('Current access')
            ->assertSeeText('Free')
            ->assertSeeText(
                'Basic practice tools with Free plan limits.'
            );
    }

    public function test_active_trial_user_sees_trial_access(): void
    {
        $user = $this->createFreeUser();

        $this->createTrial($user, 'active');

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText('Trial Mode')
            ->assertSeeText('Active · 50:00 remaining.')
            ->assertSeeText('Free plan limits still apply.');
    }

    public function test_paused_trial_user_sees_paused_trial(): void
    {
        $user = $this->createFreeUser();

        $this->createTrial($user, 'paused');

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText('Trial Mode')
            ->assertSeeText('Paused · 50:00 remaining.')
            ->assertSeeText('Resume it from the metronome.');
    }

    public function test_monthly_pro_user_sees_monthly_access(): void
    {
        $user = $this->createMonthlyProUser();

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText('Monthly Pro')
            ->assertSeeText(
                'Monthly subscription · Pro access is active.'
            )
            ->assertDontSeeText(
                'One-time purchase · Pro access does not expire.'
            );
    }

    public function test_lifetime_user_sees_lifetime_access(): void
    {
        $user = $this->createProUser();

        app(GrantLifetimePro::class)->grant(
            $user,
            'cs_billing_page_lifetime',
            'pi_billing_page_lifetime',
            'price_billing_page_lifetime'
        );

        $this->actingAs($user->refresh())
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText('Lifetime Pro')
            ->assertSeeText(
                'One-time purchase · Pro access does not expire.'
            )
            ->assertDontSeeText(
                'Monthly subscription · Pro access is active.'
            );
    }

    public function test_free_user_can_choose_monthly_or_lifetime(): void
    {
        $user = $this->createFreeUser();

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText('Choose Monthly Pro')
            ->assertSeeText('Choose Lifetime Pro')
            ->assertSee(
                'action="'.
                    route('billing.pro.monthly.checkout').
                '"',
                false
            )
            ->assertSee(
                'action="'.
                    route('billing.pro.lifetime.checkout').
                '"',
                false
            );
    }

    public function test_trial_user_can_choose_monthly_or_lifetime(): void
    {
        $user = $this->createFreeUser();

        $this->createTrial($user, 'active');

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText('Choose Monthly Pro')
            ->assertSeeText('Choose Lifetime Pro');
    }

    public function test_monthly_user_cannot_start_another_purchase(): void
    {
        $user = $this->createMonthlyProUser();

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText('Current plan')
            ->assertSeeText(
                'Available after your monthly subscription ends.'
            )
            ->assertDontSeeText('Choose Monthly Pro')
            ->assertDontSeeText('Choose Lifetime Pro')
            ->assertDontSee(
                'action="'.
                    route('billing.pro.monthly.checkout').
                '"',
                false
            )
            ->assertDontSee(
                'action="'.
                    route('billing.pro.lifetime.checkout').
                '"',
                false
            );
    }

    public function test_lifetime_user_cannot_purchase_pro_again(): void
    {
        $user = $this->createProUser();

        app(GrantLifetimePro::class)->grant(
            $user,
            'cs_billing_options_lifetime',
            'pi_billing_options_lifetime',
            'price_billing_options_lifetime'
        );

        $this->actingAs($user->refresh())
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText('Included with Lifetime Pro')
            ->assertSeeText('Owned')
            ->assertDontSeeText('Choose Monthly Pro')
            ->assertDontSeeText('Choose Lifetime Pro')
            ->assertDontSee(
                'action="'.
                    route('billing.pro.monthly.checkout').
                '"',
                false
            )
            ->assertDontSee(
                'action="'.
                    route('billing.pro.lifetime.checkout').
                '"',
                false
            );
    }

    public function test_billing_page_displays_configured_prices(): void
    {
        config()->set(
            'billing.pro.monthly_display_price',
            '$5 USD / month'
        );

        config()->set(
            'billing.pro.lifetime_display_price',
            '$40 USD one-time'
        );

        $user = $this->createFreeUser();

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText('$5 USD / month')
            ->assertSeeText('$40 USD one-time');
    }

    public function test_monthly_checkout_return_shows_confirmation_notice(): void
    {
        $user = $this->createFreeUser();

        $this->actingAs($user)
            ->get(route('billing.index', [
                'checkout' => 'monthly-success',
            ]))
            ->assertOk()
            ->assertSeeText(
                'Checkout completed. Your Pro access will appear here '
                .'after Stripe confirms the subscription.'
            );
    }

    public function test_lifetime_checkout_return_shows_confirmation_notice(): void
    {
        $user = $this->createFreeUser();

        $this->actingAs($user)
            ->get(route('billing.index', [
                'checkout' => 'lifetime-success',
            ]))
            ->assertOk()
            ->assertSeeText(
                'Payment completed. Your Lifetime Pro access will appear '
                .'here after Stripe confirms the purchase.'
            );
    }

    public function test_cancelled_checkout_shows_no_changes_notice(): void
    {
        $user = $this->createFreeUser();

        $this->actingAs($user)
            ->get(route('billing.index', [
                'checkout' => 'cancelled',
            ]))
            ->assertOk()
            ->assertSeeText(
                'Checkout was cancelled. No changes were made '
                .'to your account.'
            );
    }

    public function test_active_monthly_user_can_manage_subscription(): void
    {
        $user = $this->createMonthlyProUser();

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText(
                'Your subscription renews automatically until cancelled.'
            )
            ->assertSeeText('Manage subscription')
            ->assertSee(
                'action="'.route('billing.portal').'"',
                false
            );
    }

    public function test_monthly_grace_period_displays_end_date(): void
    {
        $user = $this->createMonthlyProUser();

        $endsAt = now()->addDays(10)->startOfDay();

        $user->subscription('pro')->forceFill([
            'stripe_status' => 'canceled',
            'ends_at' => $endsAt,
            'current_period_ends_at' => $endsAt,
        ])->save();

        $this->actingAs($user->refresh())
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText(
                'Your subscription ends on '
                .$endsAt->format('M j, Y')
                .'.'
            )
            ->assertSeeText('Manage subscription')
            ->assertDontSeeText('Your subscription renews on')
            ->assertDontSeeText(
                'Your subscription renews automatically until cancelled.'
            );
    }

    public function test_free_user_does_not_see_subscription_management(): void
    {
        $user = $this->createFreeUser();

        $this->actingAs($user)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertDontSeeText('Manage subscription')
            ->assertDontSee(
                'action="'.route('billing.portal').'"',
                false
            );
    }

    public function test_active_monthly_user_displays_renewal_date(): void
    {
        $user = $this->createMonthlyProUser();

        $renewsAt = now()
            ->addMonth()
            ->startOfDay();

        $subscription = $user->subscription('pro');

        $subscription->newQuery()
            ->whereKey($subscription->getKey())
            ->update([
                'current_period_ends_at' => $renewsAt,
            ]);

        $this->actingAs($user->refresh())
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSeeText(
                'Your subscription renews on '
                .$renewsAt->format('M j, Y')
                .'.'
            )
            ->assertDontSeeText(
                'Your subscription renews automatically until cancelled.'
            );
    }

    private function createTrial(
        User $user,
        string $status
    ): void {
        $trial = new TrialEntitlement();

        $trial->forceFill([
            'status' => $status,
            'granted_seconds' => 3_600,
            'used_seconds' => 600,
            'started_at' => now()->subMinute(),
            'expires_at' => now()->addDays(14),
            'paused_at' =>
                $status === 'paused' ? now() : null,
            'pause_reason' =>
                $status === 'paused' ? 'manual' : null,
            'active_session_id' =>
                $status === 'active'
                    ? (string) Str::uuid()
                    : null,
            'last_heartbeat_at' => now(),
        ]);

        $trial->user()->associate($user);
        $trial->save();
    }
}
