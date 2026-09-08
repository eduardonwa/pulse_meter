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
        beat-action="applyRandomizerTool(item.beat)"
        subdivision-action="applyRandomizerToolToSubdivision(item.beat, subdivisionIndex)"
        x-show="randomizerResult"
        x-cloak
    />

    <div
        class="creative-randomizer__grouping"
        x-show="randomizerResult"
        x-cloak
    >
        <h3 class="heading">Grouping</h3>

        <p
            x-text="randomizerPulse.grouping.join(' + ')"
        ></p>
    </div>

    <div
        class="creative-randomizer__editing"
        x-show="randomizerResult"
        x-cloak
    >
        <div>
            <h3 class="heading">Subdivision</h3>

            <label>
                <select
                    class="subdivision-selector"
                    x-model.number="randomizerPulse.subdivision"
                    @change="setRandomizerSubdivision($event.target.value)"
                >
                    <template
                        x-for="option in subdivisionOptions"
                        :key="option.value"
                    >
                        <option
                            :value="option.value"
                            x-text="option.label"
                        ></option>
                    </template>
                </select>
            </label>
        </div>

        <div>
            <h3 class="heading">Cue</h3>

            <div class="creative-randomizer__tools">
                <button
                    class="button"
                    type="button"
                    data-tether-trigger
                    @click="
                        randomizerEditorTool = 'accent';
                        startToolTether($event);
                    "
                    :class="{ 'is-selected': randomizerEditorTool === 'accent' }"
                >
                    Accent
                </button>

                <button
                    class="button"
                    type="button"
                    data-tether-trigger
                    @click="
                        randomizerEditorTool = 'click';
                        startToolTether($event);
                    "
                    :class="{ 'is-selected': randomizerEditorTool === 'click' }"
                >
                    Click
                </button>

                <button
                    class="button"
                    type="button"
                    data-tether-trigger
                    @click="
                        randomizerEditorTool = 'rest';
                        startToolTether($event);
                    "
                    :class="{ 'is-selected': randomizerEditorTool === 'rest' }"
                >
                    Rest
                </button>
            </div>
        </div>

        <div>
            <h3 class="heading">Structure</h3>

            <button
                class="button"
                type="button"
                data-tether-trigger
                @click="
                    randomizerEditorTool = 'groupStart';
                    startToolTether($event);
                "
                :class="{ 'is-selected': randomizerEditorTool === 'groupStart' }"
            >
                Group start
            </button>
        </div>
    </div>

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
                @click="randomizerPlaybackMode = 'click'"
                :class="{ 'is-selected': randomizerPlaybackMode === 'click' }"
            >
                Click
            </button>

            <button
                class="button"
                data-type="outline"
                type="button"
                @click="randomizerPlaybackMode = 'pulse'"
                :class="{ 'is-selected': randomizerPlaybackMode === 'pulse' }"
            >
                Pulse
            </button>
        </div>

        <div class="creative-randomizer__persistence">
            <button
                class="button"
                data-type="primary"
                type="button"
                x-show="randomizerResult"
                x-cloak
                @click="saveRandomizedSession()"
            >
                Save
            </button>

            <button
                class="button"
                data-type="outline"
                type="button"
                x-show="
                    randomizerResult
                    && randomizerDraft.origin === 'saved'
                "
                x-cloak
                @click="saveRandomizedSessionAs()"
            >
                Save as copy
            </button>

            <button
                class="button"
                data-type="outline"
                type="button"
                @click="openRandomizerSessionsDialog()"
            >
                Saved sessions
                <span
                    x-show="randomizerSavedSessions.length"
                    x-text="`(${randomizerSavedSessions.length})`"
                ></span>
            </button>
        </div>
    </div>
</div>
