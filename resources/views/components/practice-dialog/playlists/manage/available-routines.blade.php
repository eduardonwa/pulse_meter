@props([
    'playlist',
    'routines',
])

<section class="manage-playlist-dialog__available">
    {{-- available routines --}}
    <header class="manage-playlist-dialog__section-heading">
        <div>
            <h3>
                Available Routines
            </h3>

            <p>
                Add routines to the ordered playlist.
            </p>
        </div>
    </header>

    <div class="dialog-shell__list manage-playlist-dialog__available-list">
        @foreach ($routines as $routineOption)
            @php
                $routineId = (int) $routineOption['id'];

                $isStarter =
                    (int) (
                        $playlist['starter_routine']['id']
                        ?? 0
                    ) === $routineId;

                $isAdded = collect($playlist['items'])
                    ->contains(
                        fn ($item) =>
                            (int) $item['routine']['id']
                            === $routineId
                    );
            @endphp

            <article class="manage-playlist-dialog__available-item" wire:key="available-routine-{{ $routineOption['id'] }}">
                <div class="routine-info">
                    <strong class="routine-name"> {{ $routineOption['name'] }} </strong>
                    <small class="exercise-count">
                        {{ $routineOption['steps_count'] }}
                        {{ $routineOption['steps_count'] === 1 ? 'exercise' : 'exercises' }}
                    </small>
                </div>

                <button
                    type="button"
                    class="button"
                    data-type="secondary"
                    wire:click="addRoutineToPlaylist({{ $routineOption['id'] }})"
                    wire:loading.attr="disabled"
                    wire:target="addRoutineToPlaylist({{ $routineOption['id'] }})"
                    @disabled($isStarter || $isAdded)
                >
                    @if ($isStarter)
                        Starter
                    @elseif ($isAdded)
                        Added
                    @else
                        <span
                            wire:loading.remove
                            wire:target="
                                addRoutineToPlaylist(
                                    {{ $routineOption['id'] }}
                                )
                            "
                        >
                            Add
                        </span>

                        <span
                            wire:loading
                            wire:target="
                                addRoutineToPlaylist(
                                    {{ $routineOption['id'] }}
                                )
                            "
                        >
                            Adding...
                        </span>
                    @endif
                </button>
            </article>
        @endforeach
    </div>
</section>