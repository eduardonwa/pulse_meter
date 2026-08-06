<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Carbon\CarbonImmutable;

class BillingPageController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $hasLifetimePro = $user->hasLifetimePro();
        $hasMonthlyPro = $user->subscribed('pro');

        $monthlySubscription = $hasMonthlyPro
            ? $user->subscription('pro')
            : null;

        $currentPeriodEnd =
            $monthlySubscription?->getRawOriginal(
                'current_period_ends_at'
            );

        $monthlyRenewalDate = is_string($currentPeriodEnd)
            && $currentPeriodEnd !== ''
                ? CarbonImmutable::parse(
                    $currentPeriodEnd,
                    'UTC'
                )
                : null;

        $monthlyManagement = match (true) {
            $monthlySubscription?->onGracePeriod()
                && $monthlySubscription->ends_at !== null => [
                    'status' => 'cancelling',
                    'detail' =>
                        'Your subscription ends on '
                        .$monthlySubscription->ends_at->format('M j, Y')
                        .'.',
                ],

            $monthlySubscription !== null
                && $monthlyRenewalDate !== null => [
                    'status' => 'active',
                    'detail' =>
                        'Your subscription renews on '
                        .$monthlyRenewalDate->format('M j, Y')
                        .'.',
                ],

            $monthlySubscription !== null => [
                'status' => 'active',
                'detail' =>
                    'Your subscription renews automatically until cancelled.',
            ],

            default => null,
        };

        $canPurchaseMonthly = ! $hasLifetimePro
            && ! $hasMonthlyPro
            && ! $user->isPro();

        $canPurchaseLifetime = ! $hasLifetimePro
            && ! $hasMonthlyPro
            && ! $user->isPro();

        $checkoutNotice = match (
            $request->query('checkout')
        ) {
            'monthly-success' => [
                'status' => 'success',
                'message' =>
                    'Checkout completed. Your Pro access will appear here '
                    .'after Stripe confirms the subscription.',
            ],

            'lifetime-success' => [
                'status' => 'success',
                'message' =>
                    'Payment completed. Your Lifetime Pro access will appear '
                    .'here after Stripe confirms the purchase.',
            ],

            'cancelled' => [
                'status' => 'cancelled',
                'message' =>
                    'Checkout was cancelled. No changes were made '
                    .'to your account.',
            ],

            default => null,
        };

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
            'checkoutNotice' => $checkoutNotice,
            'monthlyManagement' => $monthlyManagement,
            'monthlyDisplayPrice' => config('billing.pro.monthly_display_price'),
            'lifetimeDisplayPrice' => config('billing.pro.lifetime_display_price'),
        ]);
    }
}