<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\OpenBillingPortal;
use Illuminate\Http\Request;

class BillingPortalController extends Controller
{
    public function __invoke(
        Request $request,
        OpenBillingPortal $portal
    ) {
        return $portal->open(
            $request->user()
        );
    }
}