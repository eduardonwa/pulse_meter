@include('livewire.practice-dialog.sections')

{{-- INDEX --}}
<div class="routine-dialog">
    <x-practice-dialog.routines.heading />

    <div class="dialog-shell__content routine-dialog__content">
        @foreach ($routines as $routineOption)
            <x-practice-dialog.routines.item
                :routine-option="$routineOption"
                :active-routine-id="$routine['id']"
                :is-renaming="$renamingRoutineId === (int) $routineOption['id']"
                :is-first="$loop->first"
                :is-last="$loop->last"
                :can-delete="count($routines) > 1"
                wire:key="routine-{{ $routineOption['id'] }}"
            />
        @endforeach
    </div>
</div>