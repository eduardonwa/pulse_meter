<?php

namespace App\Services\Billing;

use App\Models\User;
use LogicException;

class StartMonthlyProCheckout
{
    public function start(User $user)
    {
        abort_if(
            $user->isPro() || $user->subscribed('pro'),
            409,
            'This account already has Pro access.'
        );

        $priceId = config(
            'billing.pro.monthly_price_id'
        );

        if (! is_string($priceId) || $priceId === '') {
            throw new LogicException(
                'The monthly Pro price is not configured.'
            );
        }

        return $user
            ->newSubscription('pro', $priceId)
            ->checkout([
                'success_url' => url('/')
                    . '?checkout=success',

                'cancel_url' => url('/')
                    . '?checkout=cancelled',
            ]);
    }
}
