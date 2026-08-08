<?php

namespace app\Services\TrialMode;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Activate
{
    public function activate(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Un usuario Pro no necesita activar Trial Mode.
        if ($user->plan === 'pro') {
            return back()->with(
                'trial_error',
                'Your account already has Pro access'
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
                'This account has already used its Pro trial'
            );
        }

        return back()->with(
            'trial_status',
            'Trial Mode enabled. You have 60 minutes of Pro access'
        );
    }
}