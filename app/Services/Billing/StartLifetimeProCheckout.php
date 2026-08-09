<?php

namespace App\Services\Billing;

use App\Models\User;
use Laravel\Cashier\Checkout;
use LogicException;

class StartLifetimeProCheckout
{
    public function __construct(
        private ReserveLifetimeCheckoutSlot $reserveSlot
    ) {}

    public function start(User $user): Checkout
    {
        abort_if(
            $user->isPro()
                || $user->subscribed('pro')
                || $user->hasLifetimePro(),
            409,
            'This account already has Pro access.'
        );

        $priceId = config('billing.pro.lifetime_price_id');

        if (! is_string($priceId) || $priceId === '') {
            throw new LogicException(
                'The Lifetime Pro price is not configured.'
            );
        }

        $reservation = $this->reserveSlot->reserve($user);

        /*
         * Stripe exige que una sesión expire al menos
         * 30 minutos después de crearla.
         */
        $checkoutExpiresAt = now()
            ->addMinutes(31)
            ->timestamp;

        $checkout = $user->checkout(
            $priceId,
            [
                'success_url' => route('billing.index',
                    ['checkout' => 'lifetime-success']
                )
                    . '&session_id={CHECKOUT_SESSION_ID}',

                'cancel_url' => route('billing.index',
                    ['checkout' => 'cancelled']
                ),

                'expires_at' => $checkoutExpiresAt,

                'metadata' => [
                    'dorelog_user_id' => (string) $user->getKey(),
                    'dorelog_purchase' => 'pro_lifetime',
                    'dorelog_price_id' => $priceId,
                    'dorelog_lifetime_reservation' => $reservation->token,
                    'dorelog_lifetime_slot' => (string) $reservation->slot_number,
                ],
            ]
        );

        $stripeSession = $checkout
            ->asStripeCheckoutSession();

        $reservation->forceFill([
            'stripe_checkout_session_id' =>
                $stripeSession->id,
        ])->save();

        return $checkout;
    }
}