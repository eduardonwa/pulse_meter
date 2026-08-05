<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Plans\DowngradeToFree;
use Laravel\Cashier\Events\WebhookHandled;

class DowngradeProFromEndedStripeSubscription
{
    public function __construct(
        private DowngradeToFree $downgradeToFree
    ) {}

    public function handle(WebhookHandled $event): void
    {
        if (
            ($event->payload['type'] ?? null)
                !== 'customer.subscription.deleted'
        ) {
            return;
        }

        $subscription = data_get(
            $event->payload,
            'data.object'
        );

        if (! is_array($subscription)) {
            return;
        }

        $monthlyPriceId = config(
            'billing.pro.monthly_price_id'
        );

        if (
            ! is_string($monthlyPriceId)
            || $monthlyPriceId === ''
        ) {
            return;
        }

        $usesMonthlyProPrice = collect(
            data_get($subscription, 'items.data', [])
        )->contains(
            fn (mixed $item): bool =>
                is_array($item)
                && data_get($item, 'price.id')
                    === $monthlyPriceId
        );

        if (! $usesMonthlyProPrice) {
            return;
        }

        $stripeCustomerId = $subscription['customer'] ?? null;

        if (! is_string($stripeCustomerId)) {
            return;
        }

        $user = User::query()
            ->where('stripe_id', $stripeCustomerId)
            ->first();

        if (! $user) {
            return;
        }

        /*
         * Protege el caso poco común donde terminó una suscripción
         * anterior, pero ya existe otra suscripción Pro activa.
         */
        if ($user->subscribed('pro')) {
            return;
        }

        $this->downgradeToFree->downgrade($user);
    }
}
