<div class="creative-randomizer">
    <h2 class="heading">Randomizer</h2>

    <template x-if="randomizerResult">
        <p
            class="creative-randomizer__result"
            x-text="getRandomizerResultLabel()"
        ></p>
    </template>

    <p x-show="!randomizerResult">
        Generate a starting point for a creative session.
    </p>

    <x-metronome.pulse-beats
        class="creative-randomizer__beats"
        groups="getPatternGroups(randomizerPulse.pattern)"
        x-show="randomizerResult"
        x-cloak
    />

    <div class="creative-randomizer__controls">
        <button
            class="button"
            data-type="primary"
            type="button"
            @click="generateRandomIdea()"
        >
            <span
                x-text="
                    randomizerResult
                        ? 'Generate again'
                        : 'Generate'
                "
            ></span>
        </button>

        <div
            class="creative-randomizer__playback"
            x-show="randomizerResult"
            x-cloak
        >
            <button
                class="button"
                data-type="outline"
                type="button"
                @click="creativePlaybackMode = 'click'"
                :class="{ 'is-selected': creativePlaybackMode === 'click' }"
            >
                Click
            </button>

            <button
                class="button"
                data-type="outline"
                type="button"
                @click="creativePlaybackMode = 'pulse'"
                :class="{ 'is-selected': creativePlaybackMode === 'pulse' }"
            >
                Pulse
            </button>
        </div>
    </div>
</div>
