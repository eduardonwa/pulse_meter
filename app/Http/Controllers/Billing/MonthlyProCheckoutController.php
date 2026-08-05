<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\StartMonthlyProCheckout;
use Illuminate\Http\Request;

class MonthlyProCheckoutController extends Controller
{
    public function __invoke(
        Request $request,
        StartMonthlyProCheckout $checkout
    ) {
        return $checkout->start(
            $request->user()
        );
    }
}
