@props([
    'playlist',
    'routines',
])

@php
    $playlistRoutineIds = collect($playlist['items'])
        ->pluck('routine.id')
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp

<section class="manage-playlist-dialog__starter">
    {{-- starter routine --}}
    <header class="manage-playlist-dialog__section-heading">
        <div>
            <h3>Starter Routine</h3>

            <p>
                Plays once before the ordered playlist routines.
            </p>
        </div>
    </header>

    <div class="manage-playlist-dialog__field">
        <select class="select" id="starter-routine"
            wire:model.live.change="starterRoutineId"
            wire:loading.attr="disabled"
            wire:target="starterRoutineId"
        >
            <option value="">
                No Starter Routine
            </option>
            
            @foreach ($routines as $routineOption)
                @php
                    $isInPlaylist = in_array((int) $routineOption['id'], $playlistRoutineIds, true);
                @endphp

                <option value="{{ $routineOption['id'] }}" @disabled($isInPlaylist)>
                    {{ $routineOption['name'] }}
                    @if ($isInPlaylist) (In playlist) @endif
                </option>
            @endforeach
        </select>

        <small wire:loading wire:target="changeStarterRoutine">
            Updating Starter Routine...
        </small>
    </div>
</section>