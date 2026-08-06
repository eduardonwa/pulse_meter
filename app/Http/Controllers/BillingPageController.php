<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BillingPageController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $hasLifetimePro = $user->hasLifetimePro();
        $hasMonthlyPro = $user->subscribed('pro');

        $canPurchaseMonthly = ! $hasLifetimePro
            && ! $hasMonthlyPro
            && ! $user->isPro();

        $canPurchaseLifetime = ! $hasLifetimePro
            && ! $hasMonthlyPro
            && ! $user->isPro();

        /*
         * Validar el Trial mediante el servicio existente también
         * normaliza trials vencidos, consumidos o abandonados.
         */
        $hasActiveTrial = ! $hasLifetimePro
            && ! $hasMonthlyPro
            && ! $user->isPro()
            && $user->hasActiveTrial();

        /*
         * Se obtiene después de hasActiveTrial() para leer cualquier
         * estado que TrialAccess haya normalizado.
         */
        $trial = $user->trialEntitlement()->first();

        $billingState = match (true) {
            $hasLifetimePro => [
                'name' => 'Lifetime Pro',
                'detail' =>
                    'One-time purchase · Pro access does not expire.',
            ],

            $hasMonthlyPro => [
                'name' => 'Monthly Pro',
                'detail' =>
                    'Monthly subscription · Pro access is active.',
            ],

            /*
             * Estado defensivo por si users.plan indica Pro, pero
             * no existe una suscripción mensual ni Lifetime activo.
             */
            $user->isPro() => [
                'name' => 'Pro',
                'detail' => 'Pro access is active.',
            ],

            $hasActiveTrial => [
                'name' => 'Trial Mode',
                'detail' =>
                    "Active · {$trial->remainingTimeLabel()} remaining. "
                    .'Free plan limits still apply.',
            ],

            $trial?->status === 'paused' => [
                'name' => 'Trial Mode',
                'detail' =>
                    "Paused · {$trial->remainingTimeLabel()} remaining. "
                    .'Resume it from the metronome.',
            ],

            default => [
                'name' => 'Free',
                'detail' =>
                    'Basic practice tools with Free plan limits.',
            ],
        };

        return view('billing.index', [
            'user' => $user,
            'billingState' => $billingState,
            'hasLifetimePro' => $hasLifetimePro,
            'hasMonthlyPro' => $hasMonthlyPro,
            'canPurchaseMonthly' => $canPurchaseMonthly,
            'canPurchaseLifetime' => $canPurchaseLifetime,
        ]);
    }
}