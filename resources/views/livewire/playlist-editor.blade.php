<div class="manage-playlist-dialog">
    <header class="dialog-shell__heading manage-playlist-dialog__heading">
        <button
            type="button"
            class="button"
            data-type="icon"
            aria-label="Back to playlists"
            wire:click="$parent.stopManagingPlaylist()"
        >
            <x-heroicon-o-arrow-left-circle />
        </button>

        <h2> {{ $playlist['name'] }} </h2>

        <span aria-hidden="true"></span>
    </header>

    <div class="dialog-shell__content manage-playlist-dialog__content">
        <x-practice-dialog.playlists.manage.starter-routine
            :playlist="$playlist"
            :routines="$routines"
        />

        <x-practice-dialog.playlists.manage.playlist-routines
            :playlist="$playlist"
        />

        <x-practice-dialog.playlists.manage.available-routines
            :playlist="$playlist"
            :routines="$routines"
        />
    </div>
</div>