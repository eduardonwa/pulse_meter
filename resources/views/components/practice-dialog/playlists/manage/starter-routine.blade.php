@props([
    'playlist',
    'routines',
])

<section class="manage-playlist-dialog__starter">
    <header class="manage-playlist-dialog__section-heading">
        <div>
            <h3>
                Starter Routine
            </h3>

            <p>
                Plays once before the ordered playlist routines.
            </p>
        </div>
    </header>

    @if ($playlist['starter_routine'])
        <div class="manage-playlist-dialog__starter-current">
            <div>
                <strong>
                    {{ $playlist['starter_routine']['name'] }}
                </strong>

                <small>
                    Current Starter Routine
                </small>
            </div>

            <button
                type="button"
                class="button"
                data-type="secondary"
                wire:click="removeStarterRoutine"
                wire:loading.attr="disabled"
                wire:target="removeStarterRoutine"
            >
                <span
                    wire:loading.remove
                    wire:target="removeStarterRoutine"
                >
                    Remove
                </span>

                <span
                    wire:loading
                    wire:target="removeStarterRoutine"
                >
                    Removing...
                </span>
            </button>
        </div>
    @else
        <p class="manage-playlist-dialog__empty">
            No Starter Routine selected.
        </p>
    @endif

    <div class="dialog-shell__list manage-playlist-dialog__routine-list">
        @foreach ($routines as $routineOption)
            <article
                class="manage-playlist-dialog__routine"
                wire:key="starter-option-{{ $routineOption['id'] }}"
            >
                <div>
                    <strong>
                        {{ $routineOption['name'] }}
                    </strong>

                    <small>
                        {{ $routineOption['steps_count'] }}

                        {{
                            $routineOption['steps_count'] === 1
                                ? 'exercise'
                                : 'exercises'
                        }}
                    </small>
                </div>

                <button
                    type="button"
                    class="button"
                    data-type="secondary"
                    wire:click="
                        setStarterRoutine(
                            {{ $routineOption['id'] }}
                        )
                    "
                    wire:loading.attr="disabled"
                    wire:target="
                        setStarterRoutine(
                            {{ $routineOption['id'] }}
                        )
                    "
                    @disabled(
                        (int) (
                            $playlist['starter_routine']['id']
                            ?? 0
                        ) === (int) $routineOption['id']
                    )
                >
                    {{
                        (int) (
                            $playlist['starter_routine']['id']
                            ?? 0
                        ) === (int) $routineOption['id']
                            ? 'Selected'
                            : 'Use as starter'
                    }}
                </button>
            </article>
        @endforeach
    </div>
</section>