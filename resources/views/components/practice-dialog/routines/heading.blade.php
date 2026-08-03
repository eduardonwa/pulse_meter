<header class="dialog-shell__heading routine-dialog__heading">
    <h2 class="routine-heading">
        Choose routine
    </h2>

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