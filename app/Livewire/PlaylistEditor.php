<?php

namespace App\Livewire;

use App\Models\PracticePlaylist;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class PlaylistEditor extends Component
{
    #[Locked]
    public int $playlistId;

    public array $playlist = [];
    public array $routines = [];
    public string $starterRoutineId = '';

    /*
     * Este componente completo pertenece a Playlist Mode,
     * por lo que todas sus peticiones requieren acceso Pro.
     */
    public function boot(): void
    {
        Gate::authorize('use-pro');
    }

    public function mount(int $playlistId): void
    {
        $this->playlistId = $playlistId;

        $this->refreshPlaylist();
        $this->refreshRoutines();
    }

    private function ownedPlaylist(): PracticePlaylist
    {
        return Auth::user()
            ->practicePlaylists()
            ->whereKey($this->playlistId)
            ->firstOrFail();
    }

    private function refreshPlaylist(): void
    {
        $playlist = $this->ownedPlaylist();

        $playlist->load([
            'starterRoutine:id,name',
            'items.routine:id,name',
        ]);

        $this->starterRoutineId =
            $playlist->starter_routine_id !== null
                ? (string) $playlist->starter_routine_id
                : '';

        $this->playlist = [
            'id' => $playlist->id,
            'name' => $playlist->name,
            'starter_routine' =>
                $playlist->starterRoutine
                    ? [
                        'id' => $playlist->starterRoutine->id,
                        'name' => $playlist->starterRoutine->name,
                    ]
                    : null,
            'items_count' => $playlist->items->count(),
            'items' => $playlist->items
                ->map(fn ($item) => [
                    /*
                    * Este es el ID de la aparición dentro
                    * de la playlist, no el ID de la rutina.
                    */
                    'id' => $item->id,
                    'position' => $item->position,

                    'routine' => [
                        'id' => $item->routine->id,
                        'name' => $item->routine->name,
                    ],
                ])
                ->values()
                ->all(),
        ];
    }

    private function refreshRoutines(): void
    {
        $this->routines = Auth::user()
            ->practiceRoutines()
            ->select([
                'id',
                'name',
                'position',
            ])
            ->withCount('steps')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn ($routine) => [
                'id' => $routine->id,
                'name' => $routine->name,
                'steps_count' => $routine->steps_count,
            ])
            ->values()
            ->all();
    }

    public function setStarterRoutine(int $routineId): void
    {
        $playlist = $this->ownedPlaylist();

        $routine = Auth::user()
            ->practiceRoutines()
            ->whereKey($routineId)
            ->firstOrFail();

        if ($playlist->items()
                ->where('practice_routine_id',$routine->id)
                ->exists()
        ) {
            /*
            * Regresar el select al Starter Routine
            * que realmente está guardado.
            */
            $this->starterRoutineId =
                $playlist->starter_routine_id !== null
                    ? (string) $playlist->starter_routine_id
                    : '';
            $this->dispatch(
                'show-toast',
                type: 'error',
                message: 'Remove this routine from the playlist before using it as the Starter Routine.',
            );

            return;
        }

        $playlist->starter_routine_id = $routine->id;
        $playlist->save();

        $this->refreshPlaylist();

        $this->dispatch(
            'show-toast',
            type: 'success',
            message: "{$routine->name} is now the Starter Routine.",
        );
    }

    public function removeStarterRoutine(): void
    {
        $playlist = $this->ownedPlaylist();

        $playlist->starter_routine_id = null;
        $playlist->save();

        $this->refreshPlaylist();
    }

    public function addRoutineToPlaylist(
        int $routineId,
    ): void {
        $result = DB::transaction(
            function () use ($routineId): array {
                $user = Auth::user();

                /*
                * Bloqueamos la playlist mientras comprobamos
                * sus items y calculamos la siguiente posición.
                */
                $playlist = $user
                    ->practicePlaylists()
                    ->whereKey($this->playlistId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $routine = $user
                    ->practiceRoutines()
                    ->whereKey($routineId)
                    ->firstOrFail();

                if ((int) $playlist->starter_routine_id === (int) $routine->id) {
                    return [
                        'status' => 'starter',
                        'routine_name' => $routine->name,
                    ];
                }

                if ($playlist->items()
                        ->where('practice_routine_id', $routine->id)
                        ->exists()
                ) {
                    return [
                        'status' => 'duplicate',
                        'routine_name' => $routine->name,
                    ];
                }

                $lastPosition = $playlist->items()
                    ->max('position');

                $playlist->items()->create([
                    'practice_routine_id' => $routine->id,

                    'position' => $lastPosition === null
                        ? 0
                        : $lastPosition + 1,
                ]);

                return [
                    'status' => 'added',
                    'routine_name' => $routine->name,
                ];
            }
        );

        if ($result['status'] === 'starter') {
            $this->dispatch(
                'show-toast',
                type: 'error',
                message: "{$result['routine_name']} is already the Starter Routine.",
            );

            return;
        }

        if ($result['status'] === 'duplicate') {
            $this->dispatch(
                'show-toast',
                type: 'error',
                message: "{$result['routine_name']} is already in this playlist.",
            );

            return;
        }

        $this->refreshPlaylist();

        $this->dispatch(
            'show-toast',
            type: 'success',
            message: "{$result['routine_name']} was added to the playlist.",
        );
    }

    public function removePlaylistItem(int $itemId): void
    {
        $result = DB::transaction(
            function () use ($itemId): array {
                $playlist = Auth::user()
                    ->practicePlaylists()
                    ->whereKey($this->playlistId)
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                * Buscamos el item exclusivamente dentro de
                * la playlist del usuario.
                */
                $item = $playlist->items()
                    ->whereKey($itemId)
                    ->firstOrFail();

                $routineName = $item->routine()
                    ->value('name');

                /*
                * Esto elimina la aparición dentro de la playlist,
                * no la PracticeRoutine original.
                */
                $item->delete();

                /*
                * Reorganizamos las posiciones para evitar huecos:
                * 0, 1, 3 pasa a 0, 1, 2.
                */
                $remainingItems = $playlist->items()
                    ->lockForUpdate()
                    ->get();

                foreach ($remainingItems as $index => $remainingItem) {
                    if ((int) $remainingItem->position === $index) {
                        continue;
                    }

                    $remainingItem->position = $index;
                    $remainingItem->save();
                }

                return [
                    'routine_name' =>
                        $routineName ?? 'Routine',
                ];
            }
        );

        $this->refreshPlaylist();

        $this->dispatch(
            'show-toast',
            type: 'success',
            message: "{$result['routine_name']} was removed from the playlist.",
        );
    }

    public function movePlaylistItem(
        int $itemId,
        string $direction,
    ): void {
        abort_unless(
            in_array($direction, ['up', 'down'], true),
            422
        );

        $wasMoved = DB::transaction(
            function () use ($itemId, $direction): bool {
                $playlist = Auth::user()
                    ->practicePlaylists()
                    ->whereKey($this->playlistId)
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                * La relación items() ya ordena por position
                * y usa el id como desempate.
                */
                $items = $playlist->items()
                    ->lockForUpdate()
                    ->get();

                $currentIndex = $items->search(
                    fn ($item) =>
                        (int) $item->id === $itemId
                );

                abort_if($currentIndex === false, 404);

                $targetIndex = $direction === 'up'
                    ? $currentIndex - 1
                    : $currentIndex + 1;

                /*
                * Si el item ya está arriba o abajo del todo,
                * no hacemos ningún cambio.
                */
                if (! $items->has($targetIndex)) {
                    return false;
                }

                $currentItem = $items[$currentIndex];
                $targetItem = $items[$targetIndex];

                $currentPosition = $currentItem->position;

                $currentItem->position =
                    $targetItem->position;

                $targetItem->position =
                    $currentPosition;

                $currentItem->save();
                $targetItem->save();

                return true;
            }
        );

        if (! $wasMoved) {
            return;
        }

        $this->refreshPlaylist();
    }

    public function updatedStarterRoutineId(
        string $routineId,
    ): void {
        if ($routineId === '') {
            $this->removeStarterRoutine();

            return;
        }

        $this->setStarterRoutine((int) $routineId);
    }

    public function render(): View
    {
        return view(
            'livewire.playlist-editor'
        );
    }
}