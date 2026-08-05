<header class="dialog-shell__heading playlist-dialog__heading">
    <p class="copy">
        Manage your routines or start practicing right away.
    </p>

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

@error('playlistLimit')
    <p class="dialog-error" role="alert">
        {{ $message }}
    </p>
@enderror