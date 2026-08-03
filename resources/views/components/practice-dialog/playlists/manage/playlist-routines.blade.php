@props([
    'playlist',
])

<section class="manage-playlist-dialog__items">
    <header class="manage-playlist-dialog__section-heading">
        <div>
            <h3>
                Playlist Routines
            </h3>

            <p>
                These routines play in the displayed order.
            </p>
        </div>
    </header>

    @if (count($playlist['items']) === 0)
        <p class="manage-playlist-dialog__empty">
            No routines have been added to this playlist.
        </p>
    @else
        <div class="dialog-shell__list manage-playlist-dialog__item-list">
            @foreach ($playlist['items'] as $item)
                <article
                    class="manage-playlist-dialog__item"
                    wire:key="playlist-item-{{ $item['id'] }}"
                >
                    <div class="move-btns">
                        <button
                            type="button"
                            class="button"
                            data-type="icon"
                            aria-label="
                                Move {{ $item['routine']['name'] }} up
                            "
                            wire:click="
                                movePlaylistItem(
                                    {{ $item['id'] }},
                                    'up'
                                )
                            "
                            wire:loading.attr="disabled"
                            wire:target="movePlaylistItem"
                            @disabled($loop->first)
                        >
                            <x-heroicon-o-chevron-up />
                        </button>

                        <button
                            type="button"
                            class="button"
                            data-type="icon"
                            aria-label="
                                Move {{ $item['routine']['name'] }} down
                            "
                            wire:click="
                                movePlaylistItem(
                                    {{ $item['id'] }},
                                    'down'
                                )
                            "
                            wire:loading.attr="disabled"
                            wire:target="movePlaylistItem"
                            @disabled($loop->last)
                        >
                            <x-heroicon-o-chevron-down />
                        </button>
                    </div>

                    <span class="manage-playlist-dialog__position">
                        {{ $loop->iteration }}
                    </span>

                    <div class="manage-playlist-dialog__item-summary">
                        <strong>
                            {{ $item['routine']['name'] }}
                        </strong>
                    </div>

                    <div class="manage-playlist-dialog__item-actions">
                        <button
                            type="button"
                            class="button delete"
                            data-type="icon"
                            aria-label="
                                Remove
                                {{ $item['routine']['name'] }}
                                from playlist
                            "
                            wire:click="
                                removePlaylistItem(
                                    {{ $item['id'] }}
                                )
                            "
                            wire:loading.attr="disabled"
                            wire:target="
                                removePlaylistItem(
                                    {{ $item['id'] }}
                                )
                            "
                        >
                            <x-heroicon-o-trash />
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>