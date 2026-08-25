<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="alpha-tex-editor"
        x-data="{
            state: $wire.$entangle(@js($getStatePath())),
            bpm: $wire.$entangle(@js($getBpmStatePath())),
            playing: false,
            looping: false,
            updateTimer: null,

            refreshScore() {
                clearTimeout(this.updateTimer)

                this.updateTimer = setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('alphatab:update', {
                        detail: {
                            element: this.$refs.score,
                            alphaTex: this.state ?? '',
                            bpm: Number(this.bpm || 80),
                        },
                    }))
                }, 300)
            },
        }"
        x-init="
            $watch('state', () => refreshScore())
            $watch('bpm', () => refreshScore())

            $nextTick(() => {
                if (state && state.trim()) {
                    refreshScore()
                }
            })
        "
        x-on:alphatab:playback-state.window="
            if ($event.detail.element === $refs.score) {
                playing = $event.detail.playing
            }
        "
    >
        <textarea class="fi-input block w-full resize-y"
            x-model.debounce.300ms="state"
            rows="12"
            placeholder="Enter AlphaTex..."
        ></textarea>

        <div class="alpha-tex-editor__preview" x-show="state && state.trim()" x-cloak>
            <div wire:ignore>
                <div
                    x-ref="score"
                    data-alphatab
                    data-admin-preview="true"
                ></div>
            </div>

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

                <span class="text-sm text-gray-500" x-text="`${bpm || 80} BPM`"></span>
            </div>
        </div>
    </div>
</x-dynamic-component>