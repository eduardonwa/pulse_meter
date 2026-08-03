@props([
    'playlistId',
])

<form class="playlist-dialog__rename" wire:submit="renamePlaylist">
    <label class="sr-only" for="playlist-name-{{ $playlistId }}">
        Playlist name
    </label>

    <input
        id="playlist-name-{{ $playlistId }}"
        type="text"
        maxlength="80"
        wire:model="playlistRenameName"
        wire:loading.attr="disabled"
        wire:target="renamePlaylist"
        autofocus
    >

    @error('playlistRenameName')
        <p class="error" role="alert">
            {{ $message }}
        </p>
    @enderror

    <div class="actions">
        <button
            type="button"
            class="button"
            data-type="outline"
            wire:click="cancelRenamingPlaylist"
            wire:loading.attr="disabled"
            wire:target="renamePlaylist"
        >
            Cancel
        </button>
        <button
            type="submit"
            class="button"
            data-type="primary"
            wire:loading.attr="disabled"
            wire:target="renamePlaylist"
        >
            <span
                wire:loading.remove
                wire:target="renamePlaylist"
            >
                Save
            </span>

            <span
                wire:loading
                wire:target="renamePlaylist"
            >
                Saving...
            </span>
        </button>
    </div>
</form>