<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\StartLifetimeProCheckout;
use Illuminate\Http\Request;

class LifetimeProCheckoutController extends Controller
{
    public function __invoke(
        Request $request,
        StartLifetimeProCheckout $checkout
    ) {
        return $checkout->start(
            $request->user()
        );
    }
}