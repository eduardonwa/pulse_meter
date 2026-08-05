<?php

namespace App\Services\Practice;

use App\Models\PracticePlaylist;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreatePracticePlaylist
{
    public function create(User $user): ?PracticePlaylist
    {
        return DB::transaction(
            function () use ($user): ?PracticePlaylist {
                /*
                 * Serializa las creaciones del mismo usuario
                 * para que dos peticiones simultáneas no
                 * superen el límite.
                 */
                $lockedUser = User::query()
                    ->lockForUpdate()
                    ->findOrFail($user->getKey());

                $limit = $lockedUser->playlistLimit();

                if (
                    $limit !== null
                    && $lockedUser
                        ->practicePlaylists()
                        ->count() >= $limit
                ) {
                    return null;
                }

                $lastPosition = $lockedUser
                    ->practicePlaylists()
                    ->max('position');

                $nextPosition = $lastPosition === null
                    ? 0
                    : $lastPosition + 1;

                return $lockedUser
                    ->practicePlaylists()
                    ->create([
                        'name' =>
                            'Playlist ' . ($nextPosition + 1),
                        'starter_routine_id' => null,
                        'position' => $nextPosition,
                    ]);
            }
        );
    }
}