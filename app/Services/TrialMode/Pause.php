<?php

namespace app\Services\TrialMode;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Pause
{
    public function pause(Request $request): RedirectResponse
    {
        return DB::transaction(function () use ($request) {
            $trial = $request->user()
                ->trialEntitlement()
                ->lockForUpdate()
                ->first();

            if (! $trial || $trial->status !== 'active') {
                return back();
            }

            $now = now();

            /*
            * Cobrar los segundos usados desde
            * el último heartbeat antes de pausar.
            */
            if ($trial->last_heartbeat_at) {
                $elapsedSeconds = (int) floor(
                    $trial->last_heartbeat_at->diffInSeconds($now)
                );

                $billableSeconds = min(
                    $elapsedSeconds,
                    15,
                    $trial->remainingSeconds()
                );

                $trial->used_seconds += $billableSeconds;
            }

            /*
            * Si justo al pausar consumió todo el trial.
            */
            if ($trial->used_seconds >= $trial->granted_seconds) {
                $trial->used_seconds = $trial->granted_seconds;
                $trial->status = 'completed';
                $trial->completed_at = $now;
                $trial->paused_at = null;
                $trial->pause_reason = null;
            } else {
                $trial->status = 'paused';
                $trial->paused_at = $now;
                $trial->pause_reason = 'manual';
            }

            $trial->last_heartbeat_at = $now;
            $trial->active_session_id = null;

            $trial->save();

            return back()->with(
                'trial_status',
                'Trial Mode paused.'
            );
        });
    }
}