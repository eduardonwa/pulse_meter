<section
    class="mode-creative"
    x-show="metronome.mode === 'creative'"
    x-cloak
>
    @include('components.metronome.sessions.creative-menu')

    <div class="play mode-creative__sticky-play">
        <button
            class="button uppercase"
            data-type="play-metronome"
            type="button"
            :data-state="isPlaying ? 'stop' : 'start'"
            :disabled="creativeMode === 'randomizer' && !randomizerResult && !isPlaying"
            @click="toggle()"
        >
            <span x-text="isPlaying ? 'Stop' : 'Start'">Start</span>
        </button>
    </div>

    <div x-show="creativeMode === 'pulse-editor'" x-cloak>
        @include('components.metronome.sessions.pulse-editor')
    </div>

    <div x-show="creativeMode === 'randomizer'" x-cloak>
        @include('components.metronome.sessions.randomizer')
    </div>
</section>
