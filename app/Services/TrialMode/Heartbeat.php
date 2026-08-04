<?php

namespace app\Services\TrialMode;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Heartbeat
{
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event' => ['nullable', 'in:heartbeat,hidden'],
            'session_id' => ['required', 'uuid'],
        ]);

        $event = $data['event'] ?? 'heartbeat';
        $sessionId = $data['session_id'];

        return DB::transaction(function () use (
            $request,
            $event,
            $sessionId
        ) {
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
            * Expiración por fecha.
            */
            if (
                $trial->expires_at &&
                $trial->expires_at->lte($now)
            ) {
                $trial->status = 'expired';
                $trial->active_session_id = null;
                $trial->paused_at = null;
                $trial->pause_reason = null;
                $trial->last_heartbeat_at = $now;
                $trial->save();

                return response()->json([
                    'status' => $trial->status,
                    'remaining_seconds' => $trial->remainingSeconds(),
                    'remaining_label' => $trial->remainingTimeLabel(),
                ]);
            }

            /*
            * Si ya está pausado, completado, etc.,
            * no consume nada.
            */
            if ($trial->status !== 'active') {
                return response()->json([
                    'status' => $trial->status,
                    'remaining_seconds' => $trial->remainingSeconds(),
                    'remaining_label' => $trial->remainingTimeLabel(),
                ]);
            }

            /*
            * Otra pestaña intenta usar el trial.
            */
            if (
                $trial->active_session_id &&
                $trial->active_session_id !== $sessionId
            ) {
                /*
                * CASO 1:
                * La pestaña original sí alcanzó a avisar que se ocultó.
                */
                if (
                    $trial->pause_reason === 'visibility_grace' &&
                    $trial->paused_at
                ) {
                    $hiddenSince = $trial->paused_at->copy();

                    $hiddenSeconds = (int) floor(
                        $hiddenSince->diffInSeconds($now)
                    );

                    if ($hiddenSeconds >= 20) {
                        $billableHidden = min(20,
                            $trial->remainingSeconds()
                        );

                        $trial->used_seconds += $billableHidden;
                        $trial->last_heartbeat_at = $now;

                        if (
                            $trial->used_seconds >=
                            $trial->granted_seconds
                        ) {
                            $trial->used_seconds =
                                $trial->granted_seconds;

                            $trial->status = 'completed';
                            $trial->completed_at = $now;
                            $trial->paused_at = null;
                            $trial->pause_reason = null;
                        } else {
                            $trial->status = 'paused';

                            $trial->paused_at =
                                $hiddenSince->addSeconds(20);

                            $trial->pause_reason = 'inactivity';
                        }

                        $trial->active_session_id = null;
                        $trial->save();

                        return response()->json([
                            'status' => $trial->status,
                            'remaining_seconds' =>
                                $trial->remainingSeconds(),
                            'remaining_label' =>
                                $trial->remainingTimeLabel(),
                        ]);
                    }
                }

                /*
                * CASO 2:
                * La pestaña fue cerrada y nunca alcanzó
                * a enviar el evento "hidden".
                *
                * Si lleva 20 segundos sin heartbeat,
                * consideramos esa sesión abandonada.
                */
                if ($trial->last_heartbeat_at) {
                    $secondsSinceHeartbeat = (int) floor(
                        $trial->last_heartbeat_at
                            ->diffInSeconds($now)
                    );

                    if ($secondsSinceHeartbeat >= 20) {
                        $billableSeconds = min(
                            $secondsSinceHeartbeat,
                            20,
                            $trial->remainingSeconds()
                        );

                        $trial->used_seconds += $billableSeconds;
                        $trial->last_heartbeat_at = $now;

                        if (
                            $trial->used_seconds >=
                            $trial->granted_seconds
                        ) {
                            $trial->used_seconds =
                                $trial->granted_seconds;

                            $trial->status = 'completed';
                            $trial->completed_at = $now;
                            $trial->paused_at = null;
                            $trial->pause_reason = null;
                        } else {
                            $trial->status = 'paused';
                            $trial->paused_at = $now;
                            $trial->pause_reason = 'inactivity';
                        }

                        /*
                        * Liberamos la pestaña muerta.
                        */
                        $trial->active_session_id = null;

                        $trial->save();

                        return response()->json([
                            'status' => $trial->status,
                            'remaining_seconds' =>
                                $trial->remainingSeconds(),
                            'remaining_label' =>
                                $trial->remainingTimeLabel(),
                        ]);
                    }
                }

                return response()->json([
                    'status' => 'active_elsewhere',
                    'message' =>
                        'Trial Mode is active in another tab.',
                    'remaining_seconds' =>
                        $trial->remainingSeconds(),
                ], 409);
            }

            /*
            * Nadie posee todavía el trial.
            * Esta pestaña se convierte en la activa.
            */
            if (! $trial->active_session_id) {
                $trial->active_session_id = $sessionId;
                $trial->save();
            }

            /*
            * La pestaña se acaba de ocultar.
            */
            if ($event === 'hidden') {
                if (
                    $trial->pause_reason === 'visibility_grace' &&
                    $trial->paused_at
                ) {
                    return response()->json([
                        'status' => $trial->status,
                        'remaining_seconds' =>
                            $trial->remainingSeconds(),
                        'remaining_label' =>
                            $trial->remainingTimeLabel(),
                    ]);
                }

                /*
                * Cobrar lo utilizado mientras estaba visible
                * desde el último heartbeat.
                */
                if ($trial->last_heartbeat_at) {
                    $elapsedVisible = (int) floor(
                        $trial->last_heartbeat_at
                            ->diffInSeconds($now)
                    );

                    $billableVisible = min(
                        $elapsedVisible,
                        15,
                        $trial->remainingSeconds()
                    );

                    $trial->used_seconds += $billableVisible;
                }

                if (
                    $trial->used_seconds >=
                    $trial->granted_seconds
                ) {
                    $trial->used_seconds =
                        $trial->granted_seconds;

                    $trial->status = 'completed';
                    $trial->completed_at = $now;
                    $trial->paused_at = null;
                    $trial->pause_reason = null;
                    $trial->active_session_id = null;
                } else {
                    /*
                    * Aquí comienza la gracia de 20 segundos.
                    */
                    $trial->paused_at = $now;
                    $trial->pause_reason = 'visibility_grace';
                }

                $trial->last_heartbeat_at = $now;
                $trial->save();

                return response()->json([
                    'status' => $trial->status,
                    'remaining_seconds' =>
                        $trial->remainingSeconds(),
                    'remaining_label' =>
                        $trial->remainingTimeLabel(),
                ]);
            }

            /*
            * La pestaña dueña regresó después de haber salido.
            */
            if (
                $trial->pause_reason === 'visibility_grace' &&
                $trial->paused_at
            ) {
                $hiddenSince = $trial->paused_at->copy();

                $hiddenSeconds = (int) floor(
                    $hiddenSince->diffInSeconds($now)
                );

                $billableHidden = min(
                    $hiddenSeconds,
                    20,
                    $trial->remainingSeconds()
                );

                $trial->used_seconds += $billableHidden;
                $trial->last_heartbeat_at = $now;

                if (
                    $trial->used_seconds >=
                    $trial->granted_seconds
                ) {
                    $trial->used_seconds =
                        $trial->granted_seconds;

                    $trial->status = 'completed';
                    $trial->completed_at = $now;
                    $trial->paused_at = null;
                    $trial->pause_reason = null;
                    $trial->active_session_id = null;
                } elseif ($hiddenSeconds >= 20) {
                    $trial->status = 'paused';

                    $trial->paused_at =
                        $hiddenSince->addSeconds(20);

                    $trial->pause_reason = 'inactivity';
                    $trial->active_session_id = null;
                } else {
                    /*
                    * Regresó antes de 20 segundos.
                    * Sigue siendo la pestaña dueña.
                    */
                    $trial->paused_at = null;
                    $trial->pause_reason = null;
                }

                $trial->save();

                return response()->json([
                    'status' => $trial->status,
                    'remaining_seconds' =>
                        $trial->remainingSeconds(),
                    'remaining_label' =>
                        $trial->remainingTimeLabel(),
                ]);
            }

            /*
            * Primer heartbeat.
            */
            if (! $trial->last_heartbeat_at) {
                $trial->last_heartbeat_at = $now;
                $trial->save();

                return response()->json([
                    'status' => $trial->status,
                    'remaining_seconds' =>
                        $trial->remainingSeconds(),
                    'remaining_label' =>
                        $trial->remainingTimeLabel(),
                ]);
            }

            /*
            * La misma pestaña propietaria regresó demasiado tarde.
            *
            * Si transcurrió toda la ventana de inactividad, liquidamos
            * un máximo de 20 segundos y pausamos el Trial.
            */
            $secondsSinceHeartbeat = (int) floor(
                $trial->last_heartbeat_at
                    ->diffInSeconds($now)
            );

            if ($secondsSinceHeartbeat >= 20) {
                $billableSeconds = min(
                    $secondsSinceHeartbeat,
                    20,
                    $trial->remainingSeconds(),
                );

                $trial->used_seconds += $billableSeconds;
                $trial->last_heartbeat_at = $now;

                if (
                    $trial->used_seconds >=
                    $trial->granted_seconds
                ) {
                    $trial->used_seconds =
                        $trial->granted_seconds;

                    $trial->status = 'completed';
                    $trial->completed_at = $now;
                    $trial->paused_at = null;
                    $trial->pause_reason = null;
                } else {
                    $trial->status = 'paused';
                    $trial->paused_at = $now;
                    $trial->pause_reason = 'inactivity';
                }

                $trial->active_session_id = null;

                $trial->save();

                return response()->json([
                    'status' => $trial->status,
                    'remaining_seconds' =>
                        $trial->remainingSeconds(),
                    'remaining_label' =>
                        $trial->remainingTimeLabel(),
                ]);
            }

            /*
            * Heartbeat normal.
            */
            $elapsedSeconds = (int) floor(
                $trial->last_heartbeat_at
                    ->diffInSeconds($now)
            );

            $billableSeconds = min(
                $elapsedSeconds,
                15,
                $trial->remainingSeconds()
            );

            $trial->used_seconds += $billableSeconds;
            $trial->last_heartbeat_at = $now;

            if (
                $trial->used_seconds >=
                $trial->granted_seconds
            ) {
                $trial->used_seconds =
                    $trial->granted_seconds;

                $trial->status = 'completed';
                $trial->completed_at = $now;
                $trial->active_session_id = null;
            }

            $trial->save();

            return response()->json([
                'status' => $trial->status,
                'remaining_seconds' =>
                    $trial->remainingSeconds(),
                'remaining_label' =>
                    $trial->remainingTimeLabel(),
            ]);
        });
    }
}