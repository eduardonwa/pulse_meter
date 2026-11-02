@props([
    'playlistOption',
    'activePlaylistId',
    'isRenaming' => false,
    'isFirst' => false,
    'isLast' => false
])

<div {{ $attributes->class('playlist-dialog__item') }}>
    @if (! $isRenaming)
        <div>
            <div class="move-btns" wire:sort:handle aria-label="Drag to reorder {{ $playlistOption['name'] }}">
                <x-heroicon-o-bars-3 />
            </div>

            <section>
                <div class="information">
                    <a wire:navigate
                        href="{{ route('welcome', ['playlist' => $playlistOption['id']]) }}"
                        @if ((int) $playlistOption['id'] === (int) $activePlaylistId)
                            aria-current="page"
                        @endif
                    >
                        <h2 class="heading">{{ $playlistOption['name'] }}</h2>
                        <small>
                            {{ $playlistOption['items_count'] }}
                            {{ $playlistOption['items_count'] === 1 ? 'routine' : 'routines' }}
                            @if ($playlistOption['starter_routine'])
                                · Starter: {{ $playlistOption['starter_routine']['name'] }}
                            @endif
                        </small>
                    </a>
                </div>

                <div class="actions">
                    <button class="button" data-type="icon-text" type="button"
                        aria-label="Rename {{ $playlistOption['name'] }}"
                        wire:click="startRenamingPlaylist({{ $playlistOption['id'] }})"
                    >
                        <x-heroicon-o-pencil-square />
                        Rename
                    </button>

                    <button class="button" data-type="icon-text" type="button"
                        aria-label="Delete {{ $playlistOption['name'] }}"
                        wire:loading.attr="disabled"
                        wire:target="deletePlaylist"
                        @click.stop="openConfirmModal({
                            title: 'Delete playlist?',
                            message: 'This playlist will be deleted. Its routines will not be deleted.',
                            confirmLabel: 'Delete',
                            action: () => $wire.deletePlaylist({{ $playlistOption['id'] }})
                        })"
                    >
                        <x-heroicon-o-trash />
                        Delete
                    </button>
                </div>

                <button class="button" data-type="primary" type="button"
                    wire:click="managePlaylist({{ $playlistOption['id'] }})"
                    wire:loading.attr="disabled"
                    wire:target="managePlaylist"
                    aria-label="Manage {{ $playlistOption['name'] }}"
                >
                    Manage playlist
                </button>
            </section>
        </div>
    @else
        <x-practice-dialog.playlists.rename-form
            :playlist-id="$playlistOption['id']"
        />
    @endif
</div>