<?php

namespace App\Services\Plans;

use App\Models\LifetimeEntitlement;
use App\Models\LifetimePurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class GrantLifetimePro
{
    public function __construct(
        private UpgradeToPro $upgradeToPro
    ) {}

    public function grant(
        User $user,
        string $checkoutSessionId,
        ?string $paymentIntentId,
        string $priceId
    ): LifetimeEntitlement {
        [$entitlement, $shouldUpgrade] = DB::transaction(
            function () use (
                $user,
                $checkoutSessionId,
                $paymentIntentId,
                $priceId
            ): array {
                $lockedUser = User::query()
                    ->lockForUpdate()
                    ->findOrFail($user->getKey());

                $purchase = LifetimePurchase::query()
                    ->firstOrCreate(
                        [
                            'stripe_checkout_session_id' =>
                                $checkoutSessionId,
                        ],
                        [
                            'user_id' => $lockedUser->getKey(),
                            'stripe_payment_intent_id' =>
                                $paymentIntentId,
                            'stripe_price_id' => $priceId,
                            'purchased_at' => now(),
                        ]
                    );

                /*
                 * Este Checkout ya había sido procesado.
                 * Nunca concede ni reactiva acceso nuevamente.
                 */
                if (! $purchase->wasRecentlyCreated) {
                    if (
                        $purchase->user_id
                            !== $lockedUser->getKey()
                    ) {
                        throw new LogicException(
                            'The Lifetime purchase belongs to another user.'
                        );
                    }

                    return [
                        $lockedUser
                            ->lifetimeEntitlement()
                            ->firstOrFail(),
                        false,
                    ];
                }

                $entitlement = $lockedUser
                    ->lifetimeEntitlement()
                    ->first();

                if ($entitlement === null) {
                    $entitlement = $lockedUser
                        ->lifetimeEntitlement()
                        ->create([
                            'stripe_checkout_session_id' =>
                                $checkoutSessionId,
                            'stripe_payment_intent_id' =>
                                $paymentIntentId,
                            'stripe_price_id' => $priceId,
                            'granted_at' => now(),
                        ]);

                    return [$entitlement, true];
                }

                /*
                 * La compra es nueva, pero el usuario ya tiene
                 * Lifetime activo. Se registra la compra y se
                 * conserva el entitlement original.
                 */
                if ($entitlement->isActive()) {
                    return [$entitlement, true];
                }

                /*
                 * Una compra nueva reactiva un Lifetime revocado.
                 */
                $entitlement->forceFill([
                    'stripe_checkout_session_id' =>
                        $checkoutSessionId,
                    'stripe_payment_intent_id' =>
                        $paymentIntentId,
                    'stripe_price_id' => $priceId,
                    'granted_at' => now(),
                    'revoked_at' => null,
                ])->save();

                return [$entitlement, true];
            }
        );

        if ($shouldUpgrade) {
            $this->upgradeToPro->upgrade($user);
        }

        return $entitlement->refresh();
    }
}