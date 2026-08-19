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

    @elseif (in_array($viewerType, ['guest', 'free'], true))
        <button
            class="routine-player__save button"
            data-type="icon-text"
            type="button"
            @click="useTemplateAsLocalRoutine()"
        >
            <x-heroicon-o-arrow-down-tray
                width="14"
                height="14"
                aria-hidden="true"
            />

            <span>Use this routine</span>
        </button>

    @else
        <button
            class="routine-player__save button"
            data-type="icon-text"
            type="button"

            @if ($viewerType === 'trial')
                @click="$dispatch('open-pro-upsell')"
            @else
                @click="saveTemplateCopy()"
            @endif
        >
            <x-heroicon-o-bookmark
                width="14"
                height="14"
                aria-hidden="true"
            />

            <span>Save a copy</span>

            @if ($viewerType === 'trial')
                <span class="badge badge--upsell-pro">
                    Pro
                </span>
            @endif
        </button>
    @endif
</div>