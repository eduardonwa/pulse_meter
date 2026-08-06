<?php

namespace App\Services\Billing;

use App\Models\User;

class OpenBillingPortal
{
    public function open(User $user)
    {
        abort_unless(
            $user->subscribed('pro'),
            409,
            'This account does not have a monthly subscription.'
        );

        return $user->redirectToBillingPortal(
            route('billing.index')
        );
    }
}