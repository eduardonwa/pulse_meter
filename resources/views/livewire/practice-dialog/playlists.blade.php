@include('livewire.practice-dialog.sections')

{{-- INDEX --}}
<div class="playlist-dialog">
    <x-practice-dialog.playlists.heading />

    @if (count($playlists) === 0)
        <p class="practice-dialog__empty">
            You haven't created any playlists yet.
        </p>
    @else
        <div class="dialog-shell__content playlist-dialog__content">
            @foreach ($playlists as $playlistOption)
                <x-practice-dialog.playlists.item
                    :playlist-option="$playlistOption"
                    :active-playlist-id="$activePlaylistId"
                    :is-renaming="$renamingPlaylistId === (int) $playlistOption['id']"
                    :is-first="$loop->first"
                    :is-last="$loop->last"
                    wire:key="playlist-{{ $playlistOption['id'] }}"
                />
            @endforeach
        </div>
    @endif
</div>