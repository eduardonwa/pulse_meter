<?php

namespace App\Services\TrialMode;

use App\Models\TrialEntitlement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrialAccess
{
    private const INACTIVITY_GRACE_SECONDS = 20;

    public function allows(User $user): bool
    {
        return DB::transaction(function () use ($user): bool {
            $trial = $user->trialEntitlement()
                ->lockForUpdate()
                ->first();

            if (! $trial) {
                return false;
            }

            $now = now();

            /*
             * El Trial dejó de ser válido por fecha.
             */
            if (
                $trial->expires_at
                && $trial->expires_at->lte($now)
            ) {
                $this->expire($trial, $now);

                return false;
            }

            /*
             * El Trial ya consumió todo el tiempo concedido.
             */
            if (
                $trial->used_seconds >=
                $trial->granted_seconds
            ) {
                $this->complete($trial, $now);

                return false;
            }

            /*
             * Paused, completed y expired no conceden acceso.
             */
            if ($trial->status !== 'active') {
                return false;
            }

            /*
             * Trial recién activado:
             *
             * damos una pequeña ventana para que la página cargue
             * y envíe el primer heartbeat que reclama la sesión.
             */
            if (! $trial->active_session_id) {
                $secondsWithoutSession = (int) floor(
                    $trial->started_at->diffInSeconds($now)
                );

                if (
                    $secondsWithoutSession <
                    self::INACTIVITY_GRACE_SECONDS
                ) {
                    return true;
                }

                $this->pauseForInactivity($trial, $now);

                return false;
            }

            /*
             * Una sesión activa debería tener heartbeat.
             *
             * Si todavía no existe, usamos started_at como respaldo.
             */
            $activityReference =
                $trial->last_heartbeat_at
                ?? $trial->started_at;

            $secondsSinceActivity = (int) floor(
                $activityReference->diffInSeconds($now)
            );

            /*
             * La pestaña sigue reportando actividad.
             */
            if (
                $secondsSinceActivity <
                self::INACTIVITY_GRACE_SECONDS
            ) {
                return true;
            }

            /*
             * La sesión fue abandonada.
             *
             * Liquidamos como máximo los 20 segundos de gracia,
             * igual que el flujo actual del heartbeat.
             */
            $billableSeconds = min(
                $secondsSinceActivity,
                self::INACTIVITY_GRACE_SECONDS,
                $trial->remainingSeconds(),
            );

            $trial->used_seconds += $billableSeconds;

            if (
                $trial->used_seconds >=
                $trial->granted_seconds
            ) {
                $this->complete($trial, $now);

                return false;
            }

            $this->pauseForInactivity($trial, $now);

            return false;
        }, attempts: 3);
    }

    private function pauseForInactivity(
        TrialEntitlement $trial,
        mixed $now,
    ): void {
        $trial->status = 'paused';
        $trial->paused_at = $now;
        $trial->pause_reason = 'inactivity';
        $trial->active_session_id = null;
        $trial->last_heartbeat_at = $now;

        $trial->save();
    }

    private function complete(
        TrialEntitlement $trial,
        mixed $now,
    ): void {
        $trial->used_seconds = $trial->granted_seconds;
        $trial->status = 'completed';
        $trial->completed_at ??= $now;
        $trial->paused_at = null;
        $trial->pause_reason = null;
        $trial->active_session_id = null;
        $trial->last_heartbeat_at = $now;

        $trial->save();
    }

    private function expire(
        TrialEntitlement $trial,
        mixed $now,
    ): void {
        $trial->status = 'expired';
        $trial->paused_at = null;
        $trial->pause_reason = null;
        $trial->active_session_id = null;
        $trial->last_heartbeat_at = $now;

        $trial->save();
    }
}