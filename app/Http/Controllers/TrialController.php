<?php

namespace App\Http\Controllers;

use App\Models\TrialEntitlement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrialController extends Controller
{
    public function activate(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Un usuario Pro no necesita activar Trial Mode.
        if ($user->plan === 'pro') {
            return back()->with(
                'trial_error',
                'Your account already has Pro access.'
            );
        }

        /*
         * Busca el trial asociado al usuario.
         *
         * Si no existe, lo crea.
         * Si ya existe —activo, pausado, terminado o expirado—,
         * devuelve el mismo registro y no crea otro.
         */
        $trial = $user->trialEntitlement()->firstOrCreate(
            [],
            [
                'status' => 'active',
                'granted_seconds' => 3600,
                'used_seconds' => 0,
                'started_at' => now(),
                'expires_at' => now()->addDays(15),
            ]
        );

        // Si no fue creado ahora, significa que ya usó su único trial.
        if (! $trial->wasRecentlyCreated) {
            return back()->with(
                'trial_error',
                'This account has already used its Pro trial.'
            );
        }

        return back()->with(
            'trial_status',
            'Trial Mode enabled. You have 60 minutes of Pro access.'
        );
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event' => ['nullable', 'in:heartbeat,hidden'],
        ]);

        $event = $data['event'] ?? 'heartbeat';

        return DB::transaction(function () use ($request, $event) {
            $trial = $request->user()
                ->trialEntitlement()
                ->lockForUpdate()
                ->first();

            if (! $trial) {
                return response()->json([
                    'status' => 'missing',
                    'remaining_seconds' => 0,
                ], 404);
            }

            $now = now();

            /*
            * El trial venció por fecha.
            */
            if ($trial->expires_at && $trial->expires_at->lte($now)) {
                $trial->status = 'expired';
                $trial->active_session_id = null;
                $trial->last_heartbeat_at = $now;
                $trial->save();

                return response()->json([
                    'status' => $trial->status,
                    'remaining_seconds' => $trial->remainingSeconds(),
                    'remaining_label' => $trial->remainingTimeLabel(),
                ]);
            }

            /*
            * Un trial pausado o completado ya no consume.
            */
            if ($trial->status !== 'active') {
                return response()->json([
                    'status' => $trial->status,
                    'remaining_seconds' => $trial->remainingSeconds(),
                    'remaining_label' => $trial->remainingTimeLabel(),
                ]);
            }

            /*
            * La pestaña acaba de ocultarse.
            *
            * Primero cobramos el tiempo visible transcurrido desde
            * el heartbeat anterior. Después guardamos la hora exacta
            * en la que comenzó el periodo de gracia.
            */
            if ($event === 'hidden') {
                /*
                * Evitar reiniciar la gracia si ya estaba oculta.
                */
                if (
                    $trial->pause_reason === 'visibility_grace' &&
                    $trial->paused_at
                ) {
                    return response()->json([
                        'status' => $trial->status,
                        'remaining_seconds' => $trial->remainingSeconds(),
                        'remaining_label' => $trial->remainingTimeLabel(),
                    ]);
                }

                if ($trial->last_heartbeat_at) {
                    $elapsedVisible = (int) floor(
                        $trial->last_heartbeat_at->diffInSeconds($now)
                    );

                    $billableVisible = min(
                        $elapsedVisible,
                        15,
                        $trial->remainingSeconds()
                    );

                    $trial->used_seconds += $billableVisible;
                }

                if ($trial->used_seconds >= $trial->granted_seconds) {
                    $trial->used_seconds = $trial->granted_seconds;
                    $trial->status = 'completed';
                    $trial->completed_at = $now;
                    $trial->paused_at = null;
                    $trial->pause_reason = null;
                } else {
                    /*
                    * Aquí comienza la gracia de 20 segundos.
                    * Todavía permanece active.
                    */
                    $trial->paused_at = $now;
                    $trial->pause_reason = 'visibility_grace';
                }

                $trial->last_heartbeat_at = $now;
                $trial->save();

                return response()->json([
                    'status' => $trial->status,
                    'remaining_seconds' => $trial->remainingSeconds(),
                    'remaining_label' => $trial->remainingTimeLabel(),
                ]);
            }

            /*
            * El usuario regresó después de ocultar la pestaña.
            */
            if (
                $trial->pause_reason === 'visibility_grace' &&
                $trial->paused_at
            ) {
                $hiddenSince = $trial->paused_at->copy();

                $hiddenSeconds = (int) floor(
                    $hiddenSince->diffInSeconds($now)
                );

                /*
                * Mientras estuvo fuera se cobran máximo 20 segundos.
                */
                $billableHidden = min(
                    $hiddenSeconds,
                    20,
                    $trial->remainingSeconds()
                );

                $trial->used_seconds += $billableHidden;
                $trial->last_heartbeat_at = $now;

                if ($trial->used_seconds >= $trial->granted_seconds) {
                    $trial->used_seconds = $trial->granted_seconds;
                    $trial->status = 'completed';
                    $trial->completed_at = $now;
                    $trial->paused_at = null;
                    $trial->pause_reason = null;
                    $trial->active_session_id = null;
                } elseif ($hiddenSeconds >= 20) {
                    /*
                    * Estuvo fuera 20 segundos o más:
                    * queda pausado en el segundo 20.
                    */
                    $trial->status = 'paused';
                    $trial->paused_at = $hiddenSince->addSeconds(20);
                    $trial->pause_reason = 'inactivity';
                    $trial->active_session_id = null;
                } else {
                    /*
                    * Regresó antes de los 20 segundos:
                    * continúa normalmente.
                    */
                    $trial->paused_at = null;
                    $trial->pause_reason = null;
                }

                $trial->save();

                return response()->json([
                    'status' => $trial->status,
                    'remaining_seconds' => $trial->remainingSeconds(),
                    'remaining_label' => $trial->remainingTimeLabel(),
                ]);
            }

            /*
            * Heartbeat normal mientras la pestaña está visible.
            */
            if (! $trial->last_heartbeat_at) {
                $trial->last_heartbeat_at = $now;
                $trial->save();

                return response()->json([
                    'status' => $trial->status,
                    'remaining_seconds' => $trial->remainingSeconds(),
                    'remaining_label' => $trial->remainingTimeLabel(),
                ]);
            }

            $elapsedSeconds = (int) floor(
                $trial->last_heartbeat_at->diffInSeconds($now)
            );

            $billableSeconds = min(
                $elapsedSeconds,
                15,
                $trial->remainingSeconds()
            );

            $trial->used_seconds += $billableSeconds;
            $trial->last_heartbeat_at = $now;

            if ($trial->used_seconds >= $trial->granted_seconds) {
                $trial->used_seconds = $trial->granted_seconds;
                $trial->status = 'completed';
                $trial->completed_at = $now;
                $trial->active_session_id = null;
            }

            $trial->save();

            return response()->json([
                'status' => $trial->status,
                'remaining_seconds' => $trial->remainingSeconds(),
                'remaining_label' => $trial->remainingTimeLabel(),
            ]);
        });
    }

    public function resume(Request $request): RedirectResponse
    {
        return DB::transaction(function () use ($request) {
            $trial = $request->user()
                ->trialEntitlement()
                ->lockForUpdate()
                ->first();

            if (! $trial) {
                return back()->with(
                    'trial_error',
                    'Trial Mode was not found.'
                );
            }

            $now = now();

            if ($trial->expires_at && $trial->expires_at->lte($now)) {
                $trial->status = 'expired';
                $trial->paused_at = null;
                $trial->pause_reason = null;
                $trial->active_session_id = null;
                $trial->save();

                return back()->with(
                    'trial_error',
                    'Your Trial Mode has expired.'
                );
            }

            if ($trial->remainingSeconds() <= 0) {
                $trial->status = 'completed';
                $trial->used_seconds = $trial->granted_seconds;
                $trial->completed_at ??= $now;
                $trial->paused_at = null;
                $trial->pause_reason = null;
                $trial->active_session_id = null;
                $trial->save();

                return back()->with(
                    'trial_error',
                    'Your Trial Mode is complete.'
                );
            }

            if ($trial->status !== 'paused') {
                return back()->with(
                    'trial_error',
                    'Trial Mode cannot be resumed.'
                );
            }

            $trial->status = 'active';
            $trial->paused_at = null;
            $trial->pause_reason = null;

            /*
            * El siguiente heartbeat contará a partir
            * del momento de reanudación.
            */
            $trial->last_heartbeat_at = $now;

            $trial->save();

            return back()->with(
                'trial_status',
                'Trial Mode resumed.'
            );
        });
    }
}