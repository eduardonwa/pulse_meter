@if (! in_array($viewerType, ['guest', 'free'], true))
    <div class="routine-player__controls">
        @if ($viewerHasTemplate)
            <button
                class="routine-player__save button"
                data-type="icon-text"
                type="button"
                disabled
            >
                <x-heroicon-o-check
                    width="14"
                    height="14"
                    aria-hidden="true"
                />

                <span>In your routines</span>
            </button>

        @elseif ($viewerType === 'trial')
            <button
                class="routine-player__save button"
                data-type="icon-text"
                type="button"
                @click="$dispatch('open-pro-upsell')"
            >
                <x-heroicon-o-bookmark
                    width="14"
                    height="14"
                    aria-hidden="true"
                />

                <span>Save a copy</span>

                <span class="badge badge--upsell-pro">
                    Pro
                </span>
            </button>

        @elseif (in_array($viewerType, ['pro', 'lifetime'], true))
            <button
                class="routine-player__save button"
                data-type="icon-text"
                type="button"
                @click="saveTemplateCopy()"
            >
                <x-heroicon-o-bookmark
                    width="14"
                    height="14"
                    aria-hidden="true"
                />

                <span>Save a copy</span>
            </button>
        @endif
    </div>
@endif