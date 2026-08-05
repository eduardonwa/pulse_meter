<header class="dialog-shell__heading routine-dialog__heading">
    <p class="copy">
        Build an exercise routine to sharpen your skills.
    </p>
    <button
        type="button"
        class="button"
        data-type="secondary"
        wire:click="createRoutine"
        wire:loading.attr="disabled"
        wire:target="createRoutine"
    >
        <span
            wire:loading.remove
            wire:target="createRoutine"
        >
            New routine
        </span>

        <span
            wire:loading
            wire:target="createRoutine"
        >
            Creating...
        </span>
    </button>
</header>

@error('routineLimit')
    <p class="dialog-error" role="alert">
        {{ $message }}
    </p>
@enderror