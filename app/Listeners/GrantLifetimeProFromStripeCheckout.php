<?php

namespace App\Listeners;

use App\Models\LifetimeCheckoutReservation;
use App\Models\User;
use App\Services\Plans\GrantLifetimePro;
use Laravel\Cashier\Events\WebhookReceived;

class GrantLifetimeProFromStripeCheckout
{
    public function __construct(
        private GrantLifetimePro $grantLifetimePro
    ) {}

    public function handle(WebhookReceived $event): void
    {
        if (
            ($event->payload['type'] ?? null)
                !== 'checkout.session.completed'
        ) {
            return;
        }

        $session = data_get(
            $event->payload,
            'data.object'
        );

        if (
            ! is_array($session)
            || ($session['mode'] ?? null) !== 'payment'
            || ($session['payment_status'] ?? null) !== 'paid'
        ) {
            return;
        }

        $metadata = $session['metadata'] ?? null;

        if (
            ! is_array($metadata)
            || ($metadata['dorelog_purchase'] ?? null)
                !== 'pro_lifetime'
        ) {
            return;
        }

        $lifetimePriceId = config(
            'billing.pro.lifetime_price_id'
        );

        if (
            ! is_string($lifetimePriceId)
            || $lifetimePriceId === ''
            || ($metadata['dorelog_price_id'] ?? null)
                !== $lifetimePriceId
        ) {
            return;
        }

        $userId = $metadata['dorelog_user_id'] ?? null;

        $reservationToken =
            $metadata['dorelog_lifetime_reservation']
                ?? null;

        $reservationSlot =
            $metadata['dorelog_lifetime_slot']
                ?? null;

        if (
            ! is_string($userId)
            || ! ctype_digit($userId)
            || ! is_string($reservationToken)
            || $reservationToken === ''
            || ! is_string($reservationSlot)
            || ! ctype_digit($reservationSlot)
        ) {
            return;
        }

        $checkoutSessionId = $session['id'] ?? null;
        $stripeCustomerId = $session['customer'] ?? null;
        $paymentIntentId = $session['payment_intent'] ?? null;

        if (
            ! is_string($checkoutSessionId)
            || ! is_string($stripeCustomerId)
            || (
                $paymentIntentId !== null
                && ! is_string($paymentIntentId)
            )
        ) {
            return;
        }

        $user = User::query()->find(
            (int) $userId
        );

        if (
            ! $user
            || $user->stripe_id !== $stripeCustomerId
        ) {
            return;
        }

        $reservation =
            LifetimeCheckoutReservation::query()
                ->where('token',$reservationToken)
                ->where('user_id',$user->getKey())
                ->where('slot_number',(int) $reservationSlot)
                ->where('stripe_checkout_session_id',$checkoutSessionId)
                ->first();

        if ($reservation === null) {
            return;
        }

        $this->grantLifetimePro->grant(
            $user,
            $checkoutSessionId,
            $paymentIntentId,
            $lifetimePriceId
        );

        if ($reservation->completed_at === null) {
            $reservation->forceFill([
                'completed_at' => now(),
            ])->save();
        }
    }
}