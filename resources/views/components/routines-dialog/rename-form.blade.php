@props([
    'routineId',
])

<form
    wire:submit="renameRoutine"
    class="routine-dialog__rename"
    @keydown.escape.prevent="$wire.cancelRenaming()"
>
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
            wire:click="cancelRenaming"
        >
            Cancel
        </button>
    </div>
</form>