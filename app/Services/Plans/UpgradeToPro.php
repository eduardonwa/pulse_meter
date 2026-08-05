<?php

namespace App\Services\Plans;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpgradeToPro
{
    public function upgrade(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->getKey());

            $trial = $lockedUser
                ->trialEntitlement()
                ->lockForUpdate()
                ->first();

            $lockedUser->forceFill([
                'plan' => 'pro',
            ])->save();

            if (
                ! $trial
                || ! in_array(
                    $trial->status,
                    ['active', 'paused'],
                    true
                )
            ) {
                return;
            }

            $trial->forceFill([
                'status' => 'converted',
                'converted_at' => now(),
                'active_session_id' => null,
                'paused_at' => null,
                'pause_reason' => null,
            ])->save();
        });
    }
}