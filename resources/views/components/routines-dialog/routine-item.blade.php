@props([
    'routineOption',
    'activeRoutineId',
    'isRenaming' => false,
    'isFirst' => false,
    'isLast' => false,
    'canDelete' => true,
])

<div {{ $attributes->class('routine-dialog__item') }}>
    @if (! $isRenaming)
        <div class="routine-dialog__summary">
            <div class="routine-row">
                <div class="move-btns">
                    <button
                        type="button"
                        class="button"
                        data-type="icon"
                        aria-label="
                            Move {{ $routineOption['name'] }} up
                        "
                        wire:click="moveRoutine(
                            {{ $routineOption['id'] }},
                            'up'
                        )"
                        wire:loading.attr="disabled"
                        wire:target="moveRoutine"
                        @disabled($isFirst)
                    >
                        <x-heroicon-o-chevron-up />
                    </button>

                    <button
                        type="button"
                        class="button"
                        data-type="icon"
                        aria-label="
                            Move {{ $routineOption['name'] }} down
                        "
                        wire:click="moveRoutine(
                            {{ $routineOption['id'] }},
                            'down'
                        )"
                        wire:loading.attr="disabled"
                        wire:target="moveRoutine"
                        @disabled($isLast)
                    >
                        <x-heroicon-o-chevron-down />
                    </button>
                </div>

                <a wire:navigate data-type="outline"
                    href="{{ route('welcome', [
                        'routine' => $routineOption['id'],
                    ]) }}"
                    @class([
                        'button routine-name',
                        'is-selected' =>
                            (int) $routineOption['id']
                            === (int) $activeRoutineId,
                    ])
                    @if (
                        (int) $routineOption['id']
                        === (int) $activeRoutineId
                    )
                        aria-current="page"
                    @endif
                >
                    <strong> {{ $routineOption['name'] }} </strong>

                    <small>
                        {{ $routineOption['steps_count'] }}

                        {{
                            $routineOption['steps_count'] === 1
                                ? 'exercise'
                                : 'exercises'
                        }}

                        @if ($routineOption['is_default'])
                            · Default
                        @endif
                    </small>
                </a>
            </div>

            <div class="routine-dialog__actions">
                <button class="badge badge--manage" type="button"
                    wire:click="manageExercises({{ $routineOption['id'] }})"
                    wire:loading.attr="disabled"
                    wire:target="manageExercises"
                    aria-label="Manage exercises in {{ $routineOption['name'] }}"
                >
                    Manage
                </button>

                <button class="button edit" type="button" data-type="icon"
                    aria-label="Rename {{ $routineOption['name'] }}"
                    wire:click="startRenaming( {{ $routineOption['id'] }} )"
                >
                    <x-heroicon-o-pencil-square />
                </button>

                <button class="button delete" type="button" data-type="icon"
                    aria-label="Delete {{ $routineOption['name'] }}"
                    wire:click="deleteRoutine({{ $routineOption['id'] }})"
                    wire:confirm="Delete this routine and all its exercises?"
                    wire:loading.attr="disabled"
                    wire:target="deleteRoutine"
                    @disabled(! $canDelete)
                >
                    <x-heroicon-o-trash />
                </button>
            </div>
        </div>
    @else
        <x-routines-dialog.rename-form
            :routine-id="$routineOption['id']"
        />
    @endif
</div>