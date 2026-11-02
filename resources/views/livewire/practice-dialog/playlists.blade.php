{{-- INDEX --}}
<div class="playlist-dialog">
    @if (count($playlists) === 0)
        <p class="practice-dialog__empty">
            You haven't created any playlists yet.
        </p>
    @else
        <div class="dialog-shell__content playlist-dialog__content" wire:sort="sortPlaylist">
            @foreach ($playlists as $playlistOption)
                <x-practice-dialog.playlists.item
                    :playlist-option="$playlistOption"
                    :active-playlist-id="$activePlaylistId"
                    :is-renaming="$renamingPlaylistId === (int) $playlistOption['id']"
                    :is-first="$loop->first"
                    :is-last="$loop->last"
                    wire:key="playlist-{{ $playlistOption['id'] }}" wire:sort:item="{{ $playlistOption['id'] }}"
                />
            @endforeach
        </div>
    @endif
</div>