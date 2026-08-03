@php
    $screen = match (true) {
        $managingExercisesRoutineId !== null => 'routine-exercises',
        $managingPlaylistId !== null => 'playlist-editor',
        $section === 'routines' => 'routines',
        default => 'playlists',
    };
@endphp

<div
    x-data="{
        isPracticeDialogOpen: false,

        openPracticeDialog() {
            this.isPracticeDialogOpen = true

            this.$nextTick(() => {
                this.$refs.practiceDialog.open || this.$refs.practiceDialog.showModal()
            })
        },

        closePracticeDialog() {
            this.$refs.practiceDialog?.open && this.$refs.practiceDialog.close()
            this.isPracticeDialogOpen = false
        },

        handlePracticeDialogClick(event) {
            const dialog = this.$refs.practiceDialog

            if (event.target !== dialog) {
                return
            }

            const rect = dialog.getBoundingClientRect()

            const clickedInside =
                event.clientX >= rect.left
                && event.clientX <= rect.right
                && event.clientY >= rect.top
                && event.clientY <= rect.bottom

            if (!clickedInside) {
                this.closePracticeDialog()
            }
        },
    }"
    @open-practice-dialog.window="openPracticeDialog()"
>
    @if ($usesServerPersistence && $routine && count($routines) > 0)
        <dialog class="dialog-shell practice-dialog" data-variant="practice-dialog"
            wire:ignore.self
            x-ref="practiceDialog"
            x-trap.noscroll="isPracticeDialogOpen"
            @close="isPracticeDialogOpen = false"
            @cancel.prevent="closePracticeDialog()"
            @click.self="handlePracticeDialogClick($event)"
        >
            <button class="button close-btn" data-type="icon" type="button" aria-label="Close practice dialog" @click="closePracticeDialog()">
                <x-heroicon-o-x-circle />
            </button>

            @switch($screen)
                @case('routines')
                    @include('livewire.practice-dialog.routines') {{-- INDEX --}}
                    @break
                
                @case('routine-exercises') {{-- ACCION "MANAGE" --}}
                    <livewire:routine-exercises
                        :routine-id="$managingExercisesRoutineId"
                        :key="'manage-exercises-' . $managingExercisesRoutineId"
                    />
                    @break

                @case('playlists')
                    @include('livewire.practice-dialog.playlists') {{-- INDEX --}}
                    @break    
                
                @case('playlist-editor') {{-- ACCION "MANAGE" --}}
                    <livewire:playlist-editor
                        :playlist-id="$managingPlaylistId"
                        :key="'manage-playlist-' . $managingPlaylistId"
                    />
                    @break                                
            @endswitch
            
            <x-windows.confirm-modal />
        </dialog>
    @endif
</div>