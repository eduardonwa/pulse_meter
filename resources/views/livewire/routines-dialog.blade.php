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
            wire:ignore.self
            class="dialog-shell | routine-dialog"
            data-variant="routine-dialog"
            x-ref="routineDialog"
            x-trap.noscroll="isRoutineDialogOpen"
            @close="isRoutineDialogOpen = false"
            @cancel.prevent="closeRoutineDialog()"
            @click.self="closeRoutineDialog()"
        >
            <header class="dialog-shell__heading">
                <button
                    type="button"
                    class="button"
                    data-type="icon"
                    aria-label="Close routines"
                    @click="closeRoutineDialog()"
                >
                    <x-heroicon-o-x-circle />
                </button>

                <h2 class="routine-heading">
                    Choose routine
                </h2>

                <button type="button" class="button" data-type="secondary"
                    wire:click="createRoutine"
                    wire:loading.attr="disabled"
                    wire:target="createRoutine"
                >
                    <span wire:loading.remove wire:target="createRoutine">
                        New routine
                    </span>

                    <span wire:loading wire:target="createRoutine">
                        Creating...
                    </span>
                </button>
            </header>

            <div class="dialog-shell__content">
                <div class="dialog-shell__list">
                    @foreach ($routines as $routineOption)
                        <div class="routine-dialog__item" wire:key="routine-{{ $routineOption['id'] }}">
                            @if ($renamingRoutineId!== (int) $routineOption['id'])
                                {{-- Vista normal --}}
                                <div class="routine-dialog__summary">
                                    <div class="routine-row">
                                        <div class="move-btns">
                                            <button type="button" class="button" data-type="icon" aria-label="Move {{ $routineOption['name'] }} up"
                                                wire:click="moveRoutine(
                                                    {{ $routineOption['id'] }},
                                                    'up'
                                                )"
                                                wire:loading.attr="disabled"
                                                wire:target="moveRoutine"
                                                @disabled($loop->first)
                                            >
                                                <x-heroicon-o-chevron-up />
                                            </button>

                                            <button type="button" class="button" data-type="icon" aria-label="Move {{ $routineOption['name'] }} down"
                                                wire:click="moveRoutine(
                                                    {{ $routineOption['id'] }},
                                                    'down'
                                                )"
                                                wire:loading.attr="disabled"
                                                wire:target="moveRoutine"
                                                @disabled($loop->last)
                                            >
                                                <x-heroicon-o-chevron-down />
                                            </button>
                                        </div>
                                        
                                        <a wire:navigate data-type="outline" href="{{ route('welcome', [ 'routine' => $routineOption['id'], ]) }}"
                                            @class([
                                                'button routine-name',
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
                                        {{-- Renombrar --}}
                                        <button class="button edit" data-type="icon" type="button"
                                            aria-label="Rename {{ $routineOption['name'] }}"
                                            wire:click="startRenaming({{ $routineOption['id'] }})"
                                        >
                                            <x-heroicon-o-pencil-square />
                                        </button>

                                        {{-- Eliminar --}}
                                        <button type="button delete" class="button delete" data-type="icon" aria-label="Delete {{ $routineOption['name'] }}"
                                            wire:click="deleteRoutine(
                                                {{ $routineOption['id'] }}
                                            )"
                                            wire:confirm="Delete this routine and all its exercises?"
                                            wire:loading.attr="disabled"
                                            wire:target="deleteRoutine"
                                            @disabled(count($routines) <= 1)
                                        >
                                            <x-heroicon-o-trash />
                                        </button>
                                    </div>
                                </div>
                            @else
                                {{-- Formulario rename con Livewire --}}
                                <form wire:submit="renameRoutine" class="routine-dialog__rename"
                                    @keydown.escape.prevent="$wire.cancelRenaming()"
                                >
                                    <label
                                        class="sr-only"
                                        for="routine-name-{{ $routineOption['id'] }}"
                                    >
                                        Routine name
                                    </label>

                                    <input
                                        id="routine-name-{{ $routineOption['id'] }}"
                                        type="text"
                                        wire:model="renameName"
                                        maxlength="80"
                                        required
                                        x-init="
                                            $nextTick(() => {
                                                $el.focus()
                                                $el.select()
                                            })
                                        "
                                    >

                                    @error('renameName')
                                        <small class="form-error">
                                            {{ $message }}
                                        </small>
                                    @enderror

                                    <div class="actions">
                                        <button type="submit" class="button" data-type="primary">
                                            Save
                                        </button>

                                        <button type="button" class="button" data-variant="outline" wire:click="cancelRenaming">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </dialog>
    @endif
</div>