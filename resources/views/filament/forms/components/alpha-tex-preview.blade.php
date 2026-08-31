<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="alpha-tex-editor"
        x-data="{
            state: $wire.$entangle(@js($getAlphaTexStatePath())),
            bpm: $wire.$entangle(@js($getBpmStatePath())),
            title: $wire.$entangle(@js($getTitleStatePath())),
            track: $wire.$entangle(@js($getTrackStatePath())),
            instrument: $wire.$entangle(@js($getInstrumentStatePath())),
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
                            title: this.title ?? '',
                            track: this.track ?? '',
                            instrument: Number(this.instrument || 25),
                        },
                    }))
                }, 300)
            },
        }"
        x-init="
            $watch('state', () => refreshScore())
            $watch('bpm', () => refreshScore())
            $watch('title', () => refreshScore())
            $watch('track', () => refreshScore())
            $watch('instrument', () => refreshScore())

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
        <div class="alpha-tex-editor__preview" x-show="state && state.trim()" x-cloak>
            <x-alphatab.player />
            <x-alphatab.controls />
        </div>
    </div>
</x-dynamic-component>
