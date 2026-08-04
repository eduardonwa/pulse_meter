<div class="manage-exercises-dialog">
    <header class="dialog-shell__heading manage-exercises-dialog__heading">
        <button class="button" data-type="icon" type="button"
            wire:click="$parent.stopManagingExercises()"
            aria-label="Back to routines"
        >
            <x-heroicon-o-arrow-left />
        </button>

        <small>Manage exercises</small>
        
        <div class="heading-info">
            <h2>{{ $routineName }}</h2>
            <small> {{ count($exercises) }} / {{ $exerciseLimit }} exercises </small>
        </div>
    </header>

    <div class="dialog-shell__content manage-exercises-dialog__content">
        <div class="dialog-shell__list manage-exercises-dialog__list">
            @forelse ($exercises as $exercise)
                <div class="exercise-item" wire:key="exercise-{{ $exercise['id'] }}">
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
                    
                    <div class="item-summary">
                        <strong class="item-summary__name"> {{ $exercise['name'] }} </strong>

                        <small class="item-summary__info">
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
                            @class(['is-disabled' => count($exercises) >= $exerciseLimit])
                        >
                            <x-heroicon-o-document-duplicate />
                        </button>

                        <button
                            class="button delete"
                            type="button"
                            data-type="icon"
                            aria-label="Delete {{ $exercise['name'] }}"
                            title="Delete exercise"
                            @click="
                                $dispatch('open-confirm-modal', {
                                    title: 'Delete exercise?',
                                    message: @js(
                                        'Delete “'
                                        . $exercise['name']
                                        . '”? This action cannot be undone.'
                                    ),
                                    confirmLabel: 'Delete',
                                    componentId: $wire.$id,
                                    method: 'deleteExercise',
                                    arguments: [{{ $exercise['id'] }}]
                                })
                            "
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