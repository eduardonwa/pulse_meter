<div class="manage-exercises-dialog">
    <header class="heading">
        <button class="button" data-type="icon" type="button"
            wire:click="$parent.stopManagingExercises()"
            aria-label="Back to routines"
        >
            <x-heroicon-o-arrow-left />
        </button>

        <small>Manage exercises</small>
        <h2>{{ $routineName }}</h2>
        <small> {{ count($exercises) }} / {{ $exerciseLimit }} exercises </small>
    </header>

    <div class="content">
        <div class="list">
            @forelse ($exercises as $exercise)
                <div class="item" wire:key="exercise-{{ $exercise['id'] }}">
                    <div class="move-btns">
                        <button class="button" type="button" data-type="icon" aria-label="Move {{ $exercise['name'] }} up"
                            wire:click="moveExercise(
                                {{ $exercise['id'] }},
                                'up'
                            )"
                            wire:loading.attr="disabled"
                            wire:target="moveExercise"
                            @disabled($loop->first)
                        >
                            <x-heroicon-o-chevron-up />
                        </button>

                        <button class="button" type="button" data-type="icon" aria-label="Move {{ $exercise['name'] }} down"
                            wire:click="moveExercise(
                                {{ $exercise['id'] }},
                                'down'
                            )"
                            wire:loading.attr="disabled"
                            wire:target="moveExercise"
                            @disabled($loop->last)
                        >
                            <x-heroicon-o-chevron-down />
                        </button>
                    </div>

                    <div class="summary">
                        <strong> {{ $exercise['name'] }} </strong>

                        <small>
                            {{ $exercise['bpm'] }} BPM
                            · {{ ucfirst($exercise['mode']) }}

                            @if (
                                $exercise['mode'] === 'timer'
                                && $exercise['duration_seconds']
                            )
                                · {{ gmdate(
                                    'i:s',
                                    $exercise['duration_seconds']
                                ) }}
                            @endif
                        </small>
                    </div>

                    <div class="actions">
                        <button class="button copy" type="button" data-type="icon" aria-label="Duplicate {{ $exercise['name'] }}"
                            wire:click="duplicateExercise({{ $exercise['id'] }})"
                            wire:loading.attr="disabled"
                            wire:target="duplicateExercise"
                            @disabled(count($exercises) >= $exerciseLimit)
                        >
                            <x-heroicon-o-document-duplicate />
                        </button>

                        <button class="button delete" type="button" data-type="icon" aria-label="Delete {{ $exercise['name'] }}"
                            wire:click="deleteExercise({{ $exercise['id'] }})"
                            wire:confirm="Delete this exercise?"
                            wire:loading.attr="disabled"
                            wire:target="deleteExercise"
                            @disabled(count($exercises) <= 1)
                        >
                            <x-heroicon-o-trash />
                        </button>
                    </div>
                </div>
            @empty
                <p>This routine has no exercises.</p>
            @endforelse
        </div>
    </div>
</div>