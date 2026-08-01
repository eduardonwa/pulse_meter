<div
    x-data="{
        isRoutineDialogOpen: false,

        openRoutineDialog() {
            this.isRoutineDialogOpen = true

            this.$nextTick(() => {
                if (!this.$refs.routineDialog.open) {
                    this.$refs.routineDialog.showModal()
                }
            })
        },

        closeRoutineDialog() {
            if (this.$refs.routineDialog?.open) {
                this.$refs.routineDialog.close()
            }

            this.isRoutineDialogOpen = false
        },
    }"
    @open-routines-dialog.window="openRoutineDialog()"
>
    @if (
        $usesServerPersistence
        && $routine
        && count($routines) > 0
    )
        <dialog
            class="dialog-shell | pattern-dialog"
            data-variant="pattern-dialog"
            x-ref="routineDialog"
            x-trap.noscroll="isRoutineDialogOpen"
            @close="isRoutineDialogOpen = false"
            @cancel.prevent="closeRoutineDialog()"
            @click.self="closeRoutineDialog()"
        >
            <div class="pattern-dialog__content">
                <header class="heading">
                    <button
                        type="button"
                        class="button"
                        data-type="icon"
                        aria-label="Close routines"
                        @click="closeRoutineDialog()"
                    >
                        <x-heroicon-o-x-circle />
                    </button>

                    <h2 class="pattern-heading">
                        Choose routine
                    </h2>

                    <form
                        method="POST"
                        action="{{ route(
                            'practice-routines.store'
                        ) }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="button"
                            data-type="primary"
                        >
                            New routine
                        </button>
                    </form>
                </header>

                <div class="pattern-dialog__list">
                    @foreach ($routines as $routineOption)
                        <div
                            class="routine-dialog__item"
                            x-data="{ isRenaming: false }"
                            wire:key="routine-{{ $routineOption['id'] }}"
                        >
                            {{-- Vista normal --}}
                            <div
                                class="routine-dialog__summary"
                                x-show="!isRenaming"
                            >
                                <a
                                    href="{{ route('welcome', [
                                        'routine' =>
                                            $routineOption['id'],
                                    ]) }}"
                                    data-type="outline"
                                    @class([
                                        'button',
                                        'is-selected' =>
                                            (int) $routineOption['id']
                                            === (int) $routine['id'],
                                    ])
                                    @if (
                                        (int) $routineOption['id']
                                        === (int) $routine['id']
                                    )
                                        aria-current="page"
                                    @endif
                                >
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

                                        @if (
                                            $routineOption['is_default']
                                        )
                                            · Default
                                        @endif
                                    </small>
                                </a>

                                <div class="actions">
                                    {{-- Mover arriba --}}
                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'practice-routines.move',
                                            [
                                                'practiceRoutine' =>
                                                    $routineOption['id'],
                                            ]
                                        ) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <input
                                            type="hidden"
                                            name="direction"
                                            value="up"
                                        >

                                        <button
                                            type="submit"
                                            class="button"
                                            data-type="icon"
                                            aria-label="Move {{ $routineOption['name'] }} up"
                                            @disabled($loop->first)
                                        >
                                            <x-heroicon-o-chevron-up />
                                        </button>
                                    </form>

                                    {{-- Mover abajo --}}
                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'practice-routines.move',
                                            [
                                                'practiceRoutine' =>
                                                    $routineOption['id'],
                                            ]
                                        ) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <input
                                            type="hidden"
                                            name="direction"
                                            value="down"
                                        >

                                        <button
                                            type="submit"
                                            class="button"
                                            data-type="icon"
                                            aria-label="Move {{ $routineOption['name'] }} down"
                                            @disabled($loop->last)
                                        >
                                            <x-heroicon-o-chevron-down />
                                        </button>
                                    </form>

                                    {{-- Renombrar --}}
                                    <button
                                        type="button"
                                        class="button edit"
                                        data-type="icon"
                                        aria-label="Rename {{ $routineOption['name'] }}"
                                        @click="
                                            isRenaming = true;

                                            $nextTick(() => {
                                                $refs.nameInput.focus();
                                                $refs.nameInput.select();
                                            });
                                        "
                                    >
                                        <x-heroicon-o-pencil-square />
                                    </button>

                                    {{-- Eliminar --}}
                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'practice-routines.destroy',
                                            [
                                                'practiceRoutine' =>
                                                    $routineOption['id'],
                                            ]
                                        ) }}"
                                        onsubmit="
                                            return confirm(
                                                'Delete this routine and all its exercises?'
                                            )
                                        "
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <input
                                            type="hidden"
                                            name="active_routine_id"
                                            value="{{ $routine['id'] }}"
                                        >

                                        <button
                                            type="submit"
                                            class="button delete"
                                            data-type="icon"
                                            aria-label="Delete {{ $routineOption['name'] }}"
                                            @disabled(
                                                count($routines) <= 1
                                            )
                                        >
                                            <x-heroicon-o-trash />
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Formulario rename --}}
                            <form
                                x-cloak
                                x-show="isRenaming"
                                method="POST"
                                action="{{ route(
                                    'practice-routines.update',
                                    [
                                        'practiceRoutine' =>
                                            $routineOption['id'],
                                    ]
                                ) }}"
                                class="routine-dialog__rename"
                                @keydown.escape.prevent="
                                    isRenaming = false
                                "
                            >
                                @csrf
                                @method('PATCH')

                                <label
                                    class="sr-only"
                                    for="routine-name-{{ $routineOption['id'] }}"
                                >
                                    Routine name
                                </label>

                                <input
                                    x-ref="nameInput"
                                    id="routine-name-{{ $routineOption['id'] }}"
                                    type="text"
                                    name="name"
                                    value="{{ $routineOption['name'] }}"
                                    maxlength="80"
                                    required
                                >

                                <div class="actions">
                                    <button
                                        type="submit"
                                        class="button"
                                        data-type="primary"
                                    >
                                        Save
                                    </button>

                                    <button
                                        type="button"
                                        class="button"
                                        data-variant="outline"
                                        @click="isRenaming = false"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </dialog>
    @endif
</div>