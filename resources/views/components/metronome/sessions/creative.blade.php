<section
    class="mode-creative"
    x-show="metronome.mode === 'creative'"
    x-cloak
>
    @include('components.metronome.sessions.creative-menu')

    <div x-show="creativeMode === 'pulse-editor'" x-cloak>
        @include('components.metronome.sessions.pulse-editor')
    </div>

    <div x-show="creativeMode === 'randomizer'" x-cloak>
        @include('components.metronome.sessions.randomizer')
    </div>
</section>
