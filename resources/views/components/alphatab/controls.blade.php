<div class="alpha-tex-editor__controls">
    <x-filament::button
        type="button"
        size="sm"
        x-bind:disabled="!state || !state.trim()"
        x-on:click.prevent="
            window.dispatchEvent(new CustomEvent('alphatab:play-pause', {
                detail: { element: $refs.score },
            }))
        "
    >
        <span x-text="playing ? 'Pause' : 'Play'"></span>
    </x-filament::button>

    <x-filament::button
        type="button"
        size="sm"
        color="gray"
        x-bind:disabled="!state || !state.trim()"
        x-on:click.prevent="
            looping = !looping

            window.dispatchEvent(new CustomEvent('alphatab:toggle-loop', {
                detail: { element: $refs.score },
            }))
        "
    >
        <span x-text="looping ? 'Disable loop' : 'Enable loop'"></span>
    </x-filament::button>

    <span
        class="text-sm text-gray-500"
        x-text="`${bpm || 80} BPM`"
    ></span>
</div>
