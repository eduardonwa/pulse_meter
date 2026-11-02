
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
        <div>
            {{-- Move up / down --}}
            <div>
                <button
                    type="button"
                    class="button"
                    data-type=""
                    aria-label="Move {{ $routineOption['name'] }} up"
                    wire:click="moveRoutine({{ $routineOption['id'] }}, 'up')"
                    wire:loading.attr="disabled"
                    wire:target="moveRoutine"
                    @disabled($isFirst)
                >
                    <x-heroicon-o-chevron-up />
                </button>

                <button
                    type="button"
                    class="button"
                    data-type=""
                    aria-label="Move {{ $routineOption['name'] }} down"
                    wire:click="moveRoutine({{ $routineOption['id'] }}, 'down')"
                    wire:loading.attr="disabled"
                    wire:target="moveRoutine"
                    @disabled($isLast)
                >
                    <x-heroicon-o-chevron-down />
                </button>
            </div>

            <section>
                {{-- Routine information --}}
                <div class="information">
                    @if ($routineOption['is_default'])
                        <span class="badge badge--default-routine">
                            <x-heroicon-o-star />
                        </span>
                    @endif

                    <a href="{{ route('welcome', ['routine' => $routineOption['id']]) }}" wire:navigate
                        @if ((int) $routineOption['id'] === (int) $activeRoutineId)
                            aria-current="page"
                        @endif
                    >
                        <h2 class="heading">{{ $routineOption['name'] }}</h2>
                    </a>
                </div>

                {{-- Quick actions --}}
                <div class="actions">
                    <button class="button" data-type="icon-text" type="button"
                        aria-label="Rename {{ $routineOption['name'] }}"
                        wire:click="startRenaming({{ $routineOption['id'] }})"
                    >
                        <x-heroicon-o-pencil-square />
                        Rename
                    </button>

                    <label class="button" data-type="icon-text">
                        <input
                            type="radio"
                            name="default-routine"
                            value="{{ $routineOption['id'] }}"
                            wire:click="setDefaultRoutine({{ $routineOption['id'] }})"
                            wire:loading.attr="disabled"
                            wire:target="setDefaultRoutine"
                            @checked($routineOption['is_default'])
                        >

                        <x-heroicon-o-star />

                        <span>Default</span>
                    </label>

                    <button class="button" data-type="icon-text" type="button"
                        aria-label="Delete {{ $routineOption['name'] }}"
                        wire:loading.attr="disabled"
                        wire:target="deleteRoutine"
                        @disabled(! $canDelete)
                        @click.stop="openConfirmModal({
                            title: 'Delete routine?',
                            message: 'All exercises will be deleted. Are you sure?',
                            confirmLabel: 'Delete',
                            action: () => $wire.deleteRoutine(
                                {{ $routineOption['id'] }}
                            )
                        })"
                    >
                        <x-heroicon-o-trash />
                        Delete
                    </button>
                </div>

                {{-- Manage exercises --}}
                <button class="button" data-type="primary" type="button"
                    wire:click="manageExercises({{ $routineOption['id'] }})"
                    wire:loading.attr="disabled"
                    wire:target="manageExercises"
                    aria-label="Manage exercises in {{ $routineOption['name'] }}"
                >
                    Manage exercises
                </button>
            </section>
        </div>
    @else
        <x-practice-dialog.routines.rename-form
            :routine-id="$routineOption['id']"
        />
    @endif
</div>
