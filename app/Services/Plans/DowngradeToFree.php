<?php

namespace App\Services\Plans;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DowngradeToFree
{
    public function downgrade(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->getKey());

            if ($lockedUser->hasLifetimePro()) {
                return;
            }

            $lockedUser->forceFill([
                'plan' => 'free',
            ])->save();
        });
    }
}
