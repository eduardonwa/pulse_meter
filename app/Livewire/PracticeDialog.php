<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Attributes\Url;

class PracticeDialog extends Component
{
    #[Locked]
    public ?array $routine = null;

    public array $routines = [];
    public array $playlists = [];
   
    #[Locked]
    public bool $usesServerPersistence = false;
    
    public string $section = 'routines';

    public ?int $renamingRoutineId = null;
    public string $renameName = '';

    public ?int $renamingPlaylistId = null;
    public string $playlistRenameName = '';
    
    public ?int $managingExercisesRoutineId = null;
    public ?int $managingPlaylistId = null;

    #[Url(as: 'playlist')]
    public ?int $activePlaylistId = null;
    
    public function mount(
        ?array $routine = null,
        array $routines = [],
        bool $usesServerPersistence = false,
    ): void {
        $this->routine = $routine
            ? [
                'id' => $routine['id'],
                'name' => $routine['name'],
                'position' => $routine['position'],
                'is_default' => $routine['is_default'],
            ]
            : null;

        $this->routines = array_values($routines);

        $this->usesServerPersistence =
            $usesServerPersistence;
    }

    public function startRenaming(int $routineId): void
    {
        Gate::authorize('use-pro');

        $routine = Auth::user()
            ->practiceRoutines()
            ->whereKey($routineId)
            ->firstOrFail();

        $this->renamingRoutineId = $routine->id;
        $this->renameName = $routine->name;

        $this->resetValidation();
    }

    public function cancelRenaming(): void
    {
        $this->reset([
            'renamingRoutineId',
            'renameName',
        ]);

        $this->resetValidation();
    }

    public function renameRoutine(): void
    {
        Gate::authorize('use-pro');

        abort_if(
            $this->renamingRoutineId === null,
            422
        );

        $this->renameName = trim($this->renameName);

        $validated = $this->validate([
            'renameName' => [
                'required',
                'string',
                'max:80',
            ],
        ]);

        /*
         * La consulta comienza desde el usuario para impedir
         * modificar rutinas ajenas.
         */
        $routine = Auth::user()
            ->practiceRoutines()
            ->whereKey($this->renamingRoutineId)
            ->firstOrFail();

        $routine->name = $validated['renameName'];
        $routine->save();

        /*
         * Actualizar el payload de la rutina activa cuando
         * la renombrada sea la que está abierta.
         */
        if (
            (int) ($this->routine['id'] ?? 0)
            === (int) $routine->id
        ) {
            $this->routine['name'] = $routine->name;
        }

        $this->refreshRoutines();

        /*
         * Notificar al Alpine principal para actualizar
         * el botón que está fuera del componente Livewire.
         */
        $this->dispatch(
            'routine-renamed',
            id: $routine->id,
            name: $routine->name,
        );

        $this->cancelRenaming();
    }

    private function refreshRoutines(): void
    {
        $this->routines = Auth::user()
            ->practiceRoutines()
            ->select([
                'id',
                'name',
                'position',
                'is_default',
            ])
            ->withCount('steps')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn ($routine) => [
                'id' => $routine->id,
                'name' => $routine->name,
                'position' => $routine->position,
                'is_default' =>
                    (bool) $routine->is_default,
                'steps_count' =>
                    $routine->steps_count,
            ])
            ->values()
            ->all();
    }

    public function createRoutine(): void
    {
        Gate::authorize('use-pro');

        $user = Auth::user();

        $routine = DB::transaction(function () use ($user) {
            $lastPosition = $user->practiceRoutines()
                ->max('position');

            $nextPosition = $lastPosition === null
                ? 0
                : $lastPosition + 1;

            $isFirstRoutine = $user->practiceRoutines()
                ->doesntExist();

            $routine = $user->practiceRoutines()->create([
                'name' => 'Routine ' . ($nextPosition + 1),
                'position' => $nextPosition,
                'is_default' => $isFirstRoutine,
            ]);

            $routine->steps()->create([
                'name' => 'New Exercise',
                'bpm' => 100,
                'mode' => 'timer',
                'duration_seconds' => 60,
                'position' => 0,
                'origin' => 'custom',
            ]);

            return $routine;
        });

        $this->redirect(
            route('welcome', [
                'routine' => $routine->id,
            ]),
            navigate: true,
        );
    }

    public function moveRoutine(
        int $routineId,
        string $direction,
    ): void {
        Gate::authorize('use-pro');

        abort_unless(
            in_array($direction, ['up', 'down'], true),
            422
        );

        $user = Auth::user();

        DB::transaction(function () use (
            $user,
            $routineId,
            $direction
        ) {
            /*
            * Buscamos exclusivamente entre las rutinas
            * pertenecientes al usuario.
            */
            $routines = $user->practiceRoutines()
                ->orderBy('position')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $currentIndex = $routines->search(
                fn ($routine) =>
                    (int) $routine->id === $routineId
            );

            abort_if($currentIndex === false, 404);

            $targetIndex = $direction === 'up'
                ? $currentIndex - 1
                : $currentIndex + 1;

            /*
            * Si ya está hasta arriba o hasta abajo,
            * no hacemos nada.
            */
            if (! $routines->has($targetIndex)) {
                return;
            }

            $currentRoutine = $routines[$currentIndex];
            $targetRoutine = $routines[$targetIndex];

            $currentPosition = $currentRoutine->position;

            $currentRoutine->position =
                $targetRoutine->position;

            $targetRoutine->position =
                $currentPosition;

            $currentRoutine->save();
            $targetRoutine->save();
        });

        $this->refreshRoutines();
    }

    public function deleteRoutine(int $routineId): void
    {
        Gate::authorize('use-pro');

        $user = Auth::user();

        $result = DB::transaction(
            function () use ($user, $routineId) {
                /*
                * La consulta parte del usuario:
                * una rutina ajena nunca aparece aquí.
                */
                $routines = $user->practiceRoutines()
                    ->orderBy('position')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                abort_if(
                    $routines->count() <= 1,
                    422,
                    'You cannot delete your only routine.'
                );

                $currentIndex = $routines->search(
                    fn ($routine) =>
                        (int) $routine->id === $routineId
                );

                abort_if($currentIndex === false, 404);

                $routineToDelete = $routines[$currentIndex];

                /*
                * Primero intentamos seleccionar la siguiente.
                * Si no existe, usamos la anterior.
                */
                $replacementRoutine =
                    $routines->get($currentIndex + 1)
                    ?? $routines->get($currentIndex - 1);

                abort_if($replacementRoutine === null, 422);

                $wasDefault =
                    (bool) $routineToDelete->is_default;

                $wasActive =
                    (int) ($this->routine['id'] ?? 0)
                    === (int) $routineToDelete->id;

                /*
                * Sus steps se eliminan mediante cascadeOnDelete.
                */
                $routineToDelete->delete();

                if ($wasDefault) {
                    $replacementRoutine->is_default = true;
                    $replacementRoutine->save();
                }

                return [
                    'was_active' => $wasActive,
                    'replacement_id' =>
                        (int) $replacementRoutine->id,
                ];
            }
        );

        $this->cancelRenaming();

        /*
        * Si eliminamos la rutina activa, la pantalla principal
        * necesita cargar los steps de otra rutina.
        */
        if ($result['was_active']) {
            $this->redirect(
                route('welcome', [
                    'routine' => $result['replacement_id'],
                ]),
                navigate: true,
            );

            return;
        }

        /*
        * Si eliminamos una inactiva, basta con refrescar
        * la lista del modal.
        */
        $this->refreshRoutines();
    }

    public function manageExercises(int $routineId): void
    {
        Gate::authorize('use-pro');

        $routine = Auth::user()
            ->practiceRoutines()
            ->whereKey($routineId)
            ->firstOrFail();

        $this->managingExercisesRoutineId = $routine->id;

        $this->cancelRenaming();
    }

    public function stopManagingExercises(): void
    {
        $this->managingExercisesRoutineId = null;
    }

    public function showRoutines(): void
    {
        $this->section = 'routines';

        $this->managingExercisesRoutineId = null;
        $this->managingPlaylistId = null;

        $this->cancelRenamingPlaylist();
    }

    public function showPlaylists(): void
    {
        Gate::authorize('use-pro');

        $this->section = 'playlists';

        $this->managingExercisesRoutineId = null;
        $this->managingPlaylistId = null;

        $this->cancelRenaming();
        $this->refreshPlaylists();
    }

    private function refreshPlaylists(): void
    {
        $this->playlists = Auth::user()
            ->practicePlaylists()
            ->select([
                'id',
                'name',
                'position',
                'starter_routine_id',
            ])
            ->with([
                'starterRoutine:id,name',
            ])
            ->withCount('items')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn ($playlist) => [
                'id' => $playlist->id,
                'name' => $playlist->name,
                'position' => $playlist->position,
                'items_count' => $playlist->items_count,
                'starter_routine' =>
                    $playlist->starterRoutine
                        ? [
                            'id' => $playlist->starterRoutine->id,
                            'name' => $playlist->starterRoutine->name,
                        ]
                        : null,
            ])
            ->values()
            ->all();
    }

    public function createPlaylist(): void
    {
        Gate::authorize('use-pro');

        $user = Auth::user();

        $playlist = DB::transaction(
            function () use ($user) {
                $lastPosition = $user
                    ->practicePlaylists()
                    ->max('position');

                $nextPosition = $lastPosition === null
                    ? 0
                    : $lastPosition + 1;

                $playlist = $user
                    ->practicePlaylists()
                    ->create([
                        'name' => 'New Playlist',
                        'starter_routine_id' => null,
                        'position' => $nextPosition,
                    ]);

                $playlist->name =
                    'Playlist ' . ($nextPosition + 1);

                $playlist->save();

                return $playlist;
            }
        );

        $this->refreshPlaylists();
    }

    public function startRenamingPlaylist(int $playlistId): void {
        Gate::authorize('use-pro');

        $playlist = Auth::user()
            ->practicePlaylists()
            ->whereKey($playlistId)
            ->firstOrFail();

        $this->renamingPlaylistId = $playlist->id;
        $this->playlistRenameName = $playlist->name;

        $this->cancelRenaming();
        $this->resetValidation();
    }

    public function cancelRenamingPlaylist(): void
    {
        $this->reset([
            'renamingPlaylistId',
            'playlistRenameName',
        ]);

        $this->resetValidation();
    }

    public function renamePlaylist(): void
    {
        Gate::authorize('use-pro');

        abort_if(
            $this->renamingPlaylistId === null,
            422
        );

        $this->playlistRenameName = trim(
            $this->playlistRenameName
        );

        $validated = $this->validate([
            'playlistRenameName' => [
                'required',
                'string',
                'max:80',
            ],
        ]);

        /*
        * La consulta comienza desde el usuario para impedir
        * modificar playlists ajenas.
        */
        $playlist = Auth::user()
            ->practicePlaylists()
            ->whereKey($this->renamingPlaylistId)
            ->firstOrFail();

        $playlist->name =
            $validated['playlistRenameName'];

        $playlist->save();

        $this->refreshPlaylists();
        $this->cancelRenamingPlaylist();
    }

    public function deletePlaylist(
        int $playlistId,
    ): void {
        Gate::authorize('use-pro');

        /*
        * La consulta parte del usuario para impedir
        * eliminar playlists ajenas.
        */
        $playlist = Auth::user()
            ->practicePlaylists()
            ->whereKey($playlistId)
            ->firstOrFail();

        $playlist->delete();

        if (
            $this->renamingPlaylistId
            === $playlistId
        ) {
            $this->cancelRenamingPlaylist();
        }

        $this->refreshPlaylists();
    }

    public function managePlaylist(int $playlistId): void
    {
        Gate::authorize('use-pro');

        /*
        * La consulta comienza desde el usuario para impedir
        * abrir playlists pertenecientes a otra cuenta.
        */
        $playlist = Auth::user()
            ->practicePlaylists()
            ->whereKey($playlistId)
            ->firstOrFail();

        $this->managingPlaylistId = $playlist->id;

        $this->cancelRenamingPlaylist();
    }

    public function stopManagingPlaylist(): void
    {
        $this->managingPlaylistId = null;

        $this->refreshPlaylists();
    }

    public function movePlaylist(
        int $playlistId,
        string $direction,
    ): void {
        Gate::authorize('use-pro');

        abort_unless(
            in_array($direction, ['up', 'down'], true),
            422
        );

        $user = Auth::user();

        $wasMoved = DB::transaction(
            function () use (
                $user,
                $playlistId,
                $direction,
            ): bool {
                $playlists = $user
                    ->practicePlaylists()
                    ->orderBy('position')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $currentIndex = $playlists->search(
                    fn ($playlist) =>
                        (int) $playlist->id === $playlistId
                );

                abort_if($currentIndex === false, 404);

                $targetIndex = $direction === 'up'
                    ? $currentIndex - 1
                    : $currentIndex + 1;

                if (! $playlists->has($targetIndex)) {
                    return false;
                }

                $currentPlaylist =
                    $playlists[$currentIndex];

                $targetPlaylist =
                    $playlists[$targetIndex];

                $currentPosition =
                    $currentPlaylist->position;

                $currentPlaylist->position =
                    $targetPlaylist->position;

                $targetPlaylist->position =
                    $currentPosition;

                $currentPlaylist->save();
                $targetPlaylist->save();

                return true;
            }
        );

        if (! $wasMoved) {
            return;
        }

        $this->refreshPlaylists();
    }

    public function render(): View
    {
        return view('livewire.practice-dialog');
    }
}