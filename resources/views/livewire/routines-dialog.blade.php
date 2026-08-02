<div
    x-data="{
        isRoutineDialogOpen: false,

        openRoutineDialog() {
            this.isRoutineDialogOpen = true

            this.$nextTick(() => {
                this.$refs.routineDialog.open
                    || this.$refs.routineDialog.showModal()
            })
        },

        closeRoutineDialog() {
            this.$refs.routineDialog?.open
                && this.$refs.routineDialog.close()

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
        <dialog class="dialog-shell | routine-dialog" data-variant="routine-dialog"
            wire:ignore.self
            x-ref="routineDialog"
            x-trap.noscroll="isRoutineDialogOpen"
            @close="isRoutineDialogOpen = false"
            @cancel.prevent="closeRoutineDialog()"
            @click.self="closeRoutineDialog()"
        >
            @if ($managingExercisesRoutineId === null)
                <x-routines-dialog.heading />

                <div class="dialog-shell__content">
                    <div class="dialog-shell__list routine-dialog__list">
                        @foreach ($routines as $routineOption)
                            <x-routines-dialog.routine-item
                                :routine-option="$routineOption"
                                :active-routine-id="$routine['id']"
                                :is-renaming="
                                    $renamingRoutineId
                                    === (int) $routineOption['id']
                                "
                                :is-first="$loop->first"
                                :is-last="$loop->last"
                                :can-delete="count($routines) > 1"
                                wire:key="routine-{{ $routineOption['id'] }}"
                            />
                        @endforeach
                    </div>
                </div>
            @else
                <livewire:manage-exercises-dialog
                    :routine-id="$managingExercisesRoutineId"
                    :key="'manage-exercises-' . $managingExercisesRoutineId"
                />
            @endif

            <x-windows.confirm-modal />
        </dialog>
    @endif
</div>