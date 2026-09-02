<?php

namespace App\Services;

use App\Models\PracticePlaylist;
use App\Models\PracticeRoutine;
use App\Models\User;

class PlaylistPlaybackBuilder
{
    public function build(
        User $user,
        int $playlistId,
    ): array {
        /*
         * Buscar la playlist exclusivamente dentro
         * de las playlists del usuario.
         */
        $playlist = $user->practicePlaylists()
            ->whereKey($playlistId)
            ->firstOrFail();

        /*
         * Cargar:
         *
         * 1. Starter Routine y sus ejercicios.
         * 2. Items de la playlist en su orden.
         * 3. Rutina y ejercicios de cada item.
         *
         * Las relaciones items() y steps() ya aplican
         * orderBy(position)->orderBy(id).
         */
        $playlist->load([
            'starterRoutine.steps',
            'items.routine.steps',
        ]);

        $groups = [];
        $queue = [];
        $queuePosition = 0;

        /*
         * Starter Routine siempre aparece primero.
         */
        if ($playlist->starterRoutine) {
            $group = $this->buildGroup(
                user: $user,
                routine: $playlist->starterRoutine,
                isStarter: true,
                queuePosition: $queuePosition,
            );

            $groups[] = $group['group'];

            foreach ($group['exercises'] as $exercise) {
                $queue[] = $exercise;
            }

            $queuePosition = $group['next_queue_position'];
        }

        /*
         * Después aparecen las rutinas agregadas
         * mediante practice_playlist_items.
         */
        foreach ($playlist->items as $item) {
            /*
             * Una referencia rota no debería ocurrir
             * si existen foreign keys, pero evitamos
             * construir una cola inválida.
             */
            if (! $item->routine) {
                continue;
            }

            $group = $this->buildGroup(
                user: $user,
                routine: $item->routine,
                isStarter: false,
                queuePosition: $queuePosition,
                playlistItemId: $item->id,
                playlistPosition: $item->position,
            );

            $groups[] = $group['group'];

            foreach ($group['exercises'] as $exercise) {
                $queue[] = $exercise;
            }

            $queuePosition = $group['next_queue_position'];
        }

        return [
            'active_playlist' => [
                'id' => $playlist->id,
                'name' => $playlist->name,

                'starter_routine_id' =>
                    $playlist->starter_routine_id,

                'groups_count' => count($groups),
                'exercises_count' => count($queue),
            ],

            'groups' => $groups,
            'queue' => $queue,
        ];
    }

    private function buildGroup(
        User $user,
        PracticeRoutine $routine,
        bool $isStarter,
        int $queuePosition,
        ?int $playlistItemId = null,
        ?int $playlistPosition = null,
    ): array {
        /*
         * Defensa adicional:
         * una playlist nunca debe cargar rutinas ajenas.
         */
        abort_unless(
            (int) $routine->user_id === (int) $user->id,
            404
        );

        $exercises = [];

        foreach ($routine->steps as $step) {
            $exercises[] = [
                /*
                 * "id" mantiene compatibilidad con el
                 * frontend actual, que usa step.id.
                 */
                'id' => $step->id,

                /*
                 * Nombre explícito para Playlist Mode.
                 */
                'exercise_id' => $step->id,

                /*
                 * Mantenemos también el nombre de foreign key
                 * que ya utiliza Routine Mode.
                 */
                'practice_routine_id' => $routine->id,

                'routine_id' => $routine->id,
                'routine_name' => $routine->name,
                'is_starter' => $isStarter,

                'name' => $step->name,
                'bpm' => $step->bpm,
                'time_signature_numerator' =>
                    $step->time_signature_numerator,

                'time_signature_denominator' =>
                    $step->time_signature_denominator,
                'mode' => $step->mode,
                'duration_seconds' => $step->duration_seconds,
                'alpha_tex' => $step->alpha_tex,
                'position' => $step->position,
                'origin' => $step->origin,

                'queue_position' => $queuePosition,
            ];

            $queuePosition++;
        }

        return [
            'group' => [
                'playlist_item_id' => $playlistItemId,
                'playlist_position' => $playlistPosition,

                'routine_id' => $routine->id,
                'routine_name' => $routine->name,
                'is_starter' => $isStarter,

                'exercises_count' => count($exercises),
                'exercises' => $exercises,
            ],

            'exercises' => $exercises,
            'next_queue_position' => $queuePosition,
        ];
    }
}