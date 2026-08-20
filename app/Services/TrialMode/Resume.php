<?php

namespace app\Services\TrialMode;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Resume
{
    public function resume(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'session_id' => ['required', 'uuid'],
        ]);

        $sessionId = $data['session_id'];

        return DB::transaction(function () use (
            $request,
            $sessionId
        ) {
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

            if (
                $trial->expires_at &&
                $trial->expires_at->lte($now)
            ) {
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
                $trial->used_seconds =
                    $trial->granted_seconds;

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
            * La pestaña que hizo Resume
            * se convierte en la dueña.
            */
            $trial->active_session_id = $sessionId;
            $trial->last_heartbeat_at = $now;

            $trial->save();

            return back()->with(
                'trial_status',
                'Trial Mode resumed.'
            );
        });
    }
}