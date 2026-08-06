<?php

namespace App\Listeners;

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

        if (
            ! is_string($userId)
            || ! ctype_digit($userId)
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

        $this->grantLifetimePro->grant(
            $user,
            $checkoutSessionId,
            $paymentIntentId,
            $lifetimePriceId
        );
    }
}
