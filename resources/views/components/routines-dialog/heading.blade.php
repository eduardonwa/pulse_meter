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