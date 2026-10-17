@props([
    'routine' => null,
    'routines' => null,
    'usesServerPersistence' => false,
])

<section class="metronome__panel">
    <div class="compact-beat-counter" aria-label="Current beat and measure beats">
        <span class="compact-beat-counter__current" x-text="currentBeat" aria-label="Current beat"></span>
        <span class="compact-beat-counter__divider" aria-hidden="true"></span>
        <div class="compact-beat-counter__strip" x-ref="compactBeatStrip"
            x-effect="currentBeat; getPlaybackBeatCount(); $nextTick(() => scrollActiveBeatIntoView($refs.compactBeatStrip, currentBeat))">
            <template x-for="beat in getPlaybackBeatCount()" :key="`${metronome.mode}-${beat}`">
                <span class="compact-beat-counter__beat" x-text="beat"
                    :class="{ 'is-active': currentBeat === beat }"
                    :aria-current="currentBeat === beat ? 'true' : null"></span>
            </template>
        </div>
    </div>
    <x-metronome.panel-tabs />

    <x-metronome.panel-sessions />

    <x-metronome.panel-exercises />

    <x-metronome.panel-settings />
</section>