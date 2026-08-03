@props([
    'routineId',
])

<form class="routine-dialog__rename" wire:submit="renameRoutine" @keydown.escape.prevent="$wire.cancelRenaming()">
    <label
        class="sr-only"
        for="routine-name-{{ $routineId }}"
    >
        Routine name
    </label>

    <input
        id="routine-name-{{ $routineId }}"
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
        <button
            type="button"
            class="button"
            data-variant="outline"
            wire:click="cancelRenaming"
        >
            Cancel
        </button>
        <button
            type="submit"
            class="button"
            data-type="primary"
        >
            Save
        </button>
    </div>
</form>