<?php

namespace App\Services\Billing;

use App\Models\User;
use Carbon\CarbonImmutable;

class SyncMonthlySubscriptionPeriod
{
    public function sync(
        User $user,
        array $stripeSubscription,
        array $monthlyItem
    ): void {
        $stripeSubscriptionId =
            $stripeSubscription['id'] ?? null;

        $currentPeriodEnd =
            $monthlyItem['current_period_end'] ?? null;

        if (
            ! is_string($stripeSubscriptionId)
            || ! is_int($currentPeriodEnd)
        ) {
            return;
        }

        $user->subscriptions()
            ->where('stripe_id', $stripeSubscriptionId)
            ->update([
                'current_period_ends_at' =>
                    CarbonImmutable::createFromTimestampUTC(
                        $currentPeriodEnd
                    ),
            ]);
    }
}