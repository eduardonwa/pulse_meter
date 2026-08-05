<?php

namespace App\Services\Billing;

use App\Models\User;
use LogicException;

class StartLifetimeProCheckout
{
    public function start(User $user)
    {
        abort_if(
            $user->hasLifetimePro(),
            409,
            'This account already has Lifetime Pro access.'
        );

        $priceId = config(
            'billing.pro.lifetime_price_id'
        );

        if (! is_string($priceId) || $priceId === '') {
            throw new LogicException(
                'The Lifetime Pro price is not configured.'
            );
        }

        return $user->checkout(
            $priceId,
            [
                'success_url' => url('/')
                    . '?checkout=lifetime-success'
                    . '&session_id={CHECKOUT_SESSION_ID}',

                'cancel_url' => url('/')
                    . '?checkout=cancelled',

                'metadata' => [
                    'dorelog_user_id' =>
                        (string) $user->getKey(),

                    'dorelog_purchase' =>
                        'pro_lifetime',
                ],
            ]
        );
    }
}