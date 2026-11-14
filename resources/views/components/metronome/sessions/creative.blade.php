<section
    class="mode-creative"
    x-show="metronome.mode === 'creative'"
    x-cloak
>
    <div class="play">
        <button
            class="button uppercase"
            data-type="play-metronome"
            type="button"
            :data-state="isPlaying ? 'stop' : 'start'"
            @click="toggle()"
        >
            <span x-text="isPlaying ? 'Stop' : 'Start'">Start</span>
        </button>
    </div>

    @include('components.metronome.sessions.creative-menu')

    <div x-show="creativeMode === 'pulse-editor'" x-cloak>
        @include('components.metronome.sessions.pulse-editor')
    </div>

    <div x-show="creativeMode === 'randomizer'" x-cloak>
        @include('components.metronome.sessions.randomizer')
    </div>
</section>
