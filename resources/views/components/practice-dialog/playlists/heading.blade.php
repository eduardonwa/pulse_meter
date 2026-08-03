<header class="dialog-shell__heading playlist-dialog__heading">
    <h2 class="playlist-heading">
        Choose playlist
    </h2>

    <button
        type="button"
        class="button"
        data-type="secondary"
        wire:click="createPlaylist"
        wire:loading.attr="disabled"
        wire:target="createPlaylist"
    >
        <span
            wire:loading.remove
            wire:target="createPlaylist"
        >
            New playlist
        </span>

        <span
            wire:loading
            wire:target="createPlaylist"
        >
            Creating...
        </span>
    </button>
</header>