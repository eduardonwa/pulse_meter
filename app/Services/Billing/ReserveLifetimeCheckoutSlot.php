<?php

namespace App\Services\Billing;

use App\Models\LifetimeCheckoutReservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class ReserveLifetimeCheckoutSlot
{
    public function reserve(
        User $user
    ): LifetimeCheckoutReservation {
        $limit = $this->limit();

        return DB::transaction(
            function () use (
                $user,
                $limit
            ): LifetimeCheckoutReservation {
                /*
                 * Evita dos reservaciones simultáneas
                 * para el mismo usuario.
                 */
                $lockedUser = User::query()
                    ->lockForUpdate()
                    ->findOrFail($user->getKey());

                $this->deleteExpiredReservations();

                $existingReservation =
                    LifetimeCheckoutReservation::query()
                        ->where(
                            'user_id',
                            $lockedUser->getKey()
                        )
                        ->lockForUpdate()
                        ->first();

                if ($existingReservation !== null) {
                    abort(
                        409,
                        'A Founding Lifetime checkout is already active for this account.'
                    );
                }

                $now = now();

                /*
                 * Stripe tendrá 30 minutos para completar
                 * Checkout. Conservamos el lugar 10 minutos
                 * adicionales para permitir que llegue el webhook.
                 */
                $reservedUntil = $now
                    ->copy()
                    ->addMinutes(40);

                for (
                    $slotNumber = 1;
                    $slotNumber <= $limit;
                    $slotNumber++
                ) {
                    $token = (string) Str::uuid();

                    $inserted = DB::table(
                        'lifetime_checkout_reservations'
                    )->insertOrIgnore([
                        'user_id' => $lockedUser->getKey(),
                        'slot_number' => $slotNumber,
                        'token' => $token,
                        'stripe_checkout_session_id' =>  null,
                        'reserved_until' => $reservedUntil,
                        'completed_at' =>  null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    if ($inserted === 1) {
                        return LifetimeCheckoutReservation::query()
                            ->where('token', $token)
                            ->firstOrFail();
                    }
                }

                abort(409, 'Founding Lifetime is sold out.');
            }
        );
    }

    public function remaining(): int
    {
        $this->deleteExpiredReservations();

        $occupied = LifetimeCheckoutReservation::query()
            ->occupyingSlot()
            ->count();

        return max(
            0,
            $this->limit() - $occupied
        );
    }

    private function deleteExpiredReservations(): void
    {
        LifetimeCheckoutReservation::query()
            ->where('completed_at', '=', null)
            ->where(
                'reserved_until',
                '<=',
                now()
            )
            ->delete();
    }

    private function limit(): int
    {
        $limit = (int) config('billing.pro.lifetime_limit', 50);

        if ($limit < 1 || $limit > 255) {
            throw new LogicException(
                'The Lifetime Pro limit must be between 1 and 255.'
            );
        }

        return $limit;
    }
}