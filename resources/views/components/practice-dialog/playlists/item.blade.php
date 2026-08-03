@props([
    'playlistOption',
    'activePlaylistId',
    'isRenaming' => false,
    'isFirst' => false,
    'isLast' => false
])

<div {{ $attributes->class('playlist-dialog__item') }}>
    @if (! $isRenaming)
        <div class="playlist-dialog__summary">
            <div class="playlist-dialog__row">
                <div class="move-btns">
                    <button
                        type="button"
                        class="button"
                        data-type="icon"
                        aria-label="Move {{ $playlistOption['name'] }} up"
                        wire:click="movePlaylist({{ $playlistOption['id'] }}, 'up')"
                        wire:loading.attr="disabled"
                        wire:target="movePlaylist"
                        @disabled($isFirst)
                    >
                        <x-heroicon-o-chevron-up />
                    </button>

                    <button
                        type="button"
                        class="button"
                        data-type="icon"
                        aria-label="Move {{ $playlistOption['name'] }} down"
                        wire:click="movePlaylist({{ $playlistOption['id'] }}, 'down')"
                        wire:loading.attr="disabled"
                        wire:target="movePlaylist"
                        @disabled($isLast)
                    >
                        <x-heroicon-o-chevron-down />
                    </button>
                </div>

                <a wire:navigate data-type="outline" href="{{ route('welcome', ['playlist' => $playlistOption['id']]) }}"
                    @class([
                        'button row-name',
                        'is-selected' => (int) $playlistOption['id'] === (int) $activePlaylistId,
                    ])
                    @if ((int) $playlistOption['id'] === (int) $activePlaylistId)
                        aria-current="page"
                    @endif
                >
                    <strong> {{ $playlistOption['name'] }} </strong>

                    <small>
                        {{ $playlistOption['items_count'] }}
                        {{ $playlistOption['items_count'] === 1 ? 'routine' : 'routines' }}
                        @if ($playlistOption['starter_routine'])
                            · Starter: {{ $playlistOption['starter_routine']['name'] }}
                        @endif
                    </small>
                </a>
            </div>

            <div class="playlist-dialog__actions">
                <button
                    class="badge badge--manage"
                    type="button"
                    wire:click="managePlaylist({{ $playlistOption['id'] }})"
                    wire:loading.attr="disabled"
                    wire:target="managePlaylist"
                    aria-label="Manage {{ $playlistOption['name'] }}"
                >
                    Manage
                </button>
                
                <button class="button edit" type="button" data-type="icon" aria-label="Rename {{ $playlistOption['name'] }}"
                    wire:click="startRenamingPlaylist({{ $playlistOption['id'] }})"
                >
                    <x-heroicon-o-pencil-square />
                </button>

                <button class="button delete" type="button" data-type="icon" aria-label="Delete {{ $playlistOption['name'] }}"
                    wire:click="deletePlaylist({{ $playlistOption['id'] }})"
                    wire:confirm="Delete this playlist? Its routines will not be deleted."
                    wire:loading.attr="disabled"
                    wire:target="deletePlaylist"
                >
                    <x-heroicon-o-trash />
                </button>
            </div>
        </div>
    @else
        <x-practice-dialog.playlists.rename-form
            :playlist-id="$playlistOption['id']"
        />
    @endif
</div>