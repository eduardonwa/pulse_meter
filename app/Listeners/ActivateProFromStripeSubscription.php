<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Billing\SyncMonthlySubscriptionPeriod;
use App\Services\Plans\UpgradeToPro;
use Laravel\Cashier\Events\WebhookHandled;

class ActivateProFromStripeSubscription
{
    public function __construct(
        private UpgradeToPro $upgradeToPro,
        private SyncMonthlySubscriptionPeriod $syncPeriod
    ) {}

    public function handle(WebhookHandled $event): void
    {
        if (
            ! in_array(
                $event->payload['type'] ?? null,
                [
                    'customer.subscription.created',
                    'customer.subscription.updated',
                ],
                true
            )
        ) {
            return;
        }

        $subscription = data_get(
            $event->payload,
            'data.object'
        );

        if (
            ! is_array($subscription)
            || ($subscription['status'] ?? null) !== 'active'
        ) {
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

        $monthlyItem = collect(
            data_get($subscription, 'items.data', [])
        )->first(
            fn (mixed $item): bool =>
                is_array($item)
                && data_get($item, 'price.id')
                    === $monthlyPriceId
        );

        if (! is_array($monthlyItem)) {
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

        $this->upgradeToPro->upgrade($user);
        $this->syncPeriod->sync(
            $user,
            $subscription,
            $monthlyItem
        );
    }
}
