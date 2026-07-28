<section class="metronome__main">
    <h2 class="current-beat" x-text="currentBeat"></h2>

    <article class="beats">
        <template x-for="beat in timeSignature.numerator" :key="beat">
            <div
                class="beat-mark"
                x-text="beat"
                :class="{ 'is-active': currentBeat === beat }"
            ></div>
        </template>
    </article>

    <div class="tempo-control">
        <div class="tempo-display">
            <label for="bpm">
                <span class="tempo-value" x-text="`${metronome.bpm}`"></span>
                <span class="tempo-unit | uppercase">bpm</span>
            </label>
        </div>

        <input
            class="tempo-range"
            type="range"
            min="30"
            max="400"
            value="100"
            x-model.number="metronome.bpm"
            @change="handleBpmChange()"
        >

        <div
            class="current-exercise-readout"
            x-show="activeTab === 'exercises' && activeExerciseIndex !== null"
        >
            <span x-text="getActiveExerciseName()"></span>

            <span
                class="current-exercise-readout__time"
                :class="{ 'is-counting': isPlaying && steps[activeExerciseIndex]?.mode === 'timer' }"
                x-text="getActiveExerciseTimeLabel()"
            ></span>
        </div>
    </div>

    <div class="play">
        <button
            class="button uppercase"
            data-type="play-metronome"
            :data-state="isPlaying ? 'stop' : 'start'"
            @click="toggle()"
        >
            <span
                class="play-label"
                x-text="isPlaying ? 'Stop' : 'Start'"
            ></span>
        </button>
    </div>

    <!-- TIME SIGNATURE SELECT -->
    <div class="time-signature-control">
        <label for="time-signature">
            Time Signature
        </label>

        <select
            id="time-signature"
            @change="
                const signature = timeSignatures[$event.target.selectedIndex];
                setTimeSignature(signature);
            "
        >
            <template
                x-for="signature in timeSignatures"
                :key="`${signature.numerator}/${signature.denominator}`"
            >
                <option
                    :value="`${signature.numerator}/${signature.denominator}`"
                    x-text="`${signature.numerator}/${signature.denominator}`"
                    :selected="
                        timeSignature.numerator === signature.numerator &&
                        timeSignature.denominator === signature.denominator
                    "
                ></option>
            </template>
        </select>
    </div>

    <!-- RHYTHM EDITOR -->
    <article class="time-signature">
        <template x-for="beat in timeSignature.numerator" :key="beat">
            <button
                type="button"
                class="beat-mark"
                @click="applyEditorTool(beat)"
                :class="{
                    'group-a': getGroupIndexForBeat(beat) % 2 === 0,
                    'group-b': getGroupIndexForBeat(beat) % 2 !== 0,

                    'is-group-start': pattern[beat - 1]?.groupStart,
                    'is-active': currentBeat === beat,
                    'is-accent': pattern[beat - 1]?.sound === 'accent',
                    'is-click': pattern[beat - 1]?.sound === 'click',
                    'is-rest': pattern[beat - 1]?.sound === 'rest'
                }"
            >
                <span
                    x-text="
                        pattern[beat - 1]?.sound === 'accent'
                            ? 'A'
                            : pattern[beat - 1]?.sound === 'click'
                                ? 'C'
                                : pattern[beat - 1]?.sound === 'rest'
                                    ? 'R'
                                    : '-'
                    "
                ></span>

                <small x-text="beat"></small>
            </button>
        </template>

        <div
            x-text="getGroupingFromPattern().join(' + ')"
        ></div>

        <div class="rhythm-tools">
            <button
                type="button"
                @click="editorTool = 'accent'"
                :class="{ 'is-selected': editorTool === 'accent' }"
            >
                Accent
            </button>

            <button
                type="button"
                @click="editorTool = 'click'"
                :class="{ 'is-selected': editorTool === 'click' }"
            >
                Click
            </button>

            <button
                type="button"
                @click="editorTool = 'rest'"
                :class="{ 'is-selected': editorTool === 'rest' }"
            >
                Rest
            </button>

            <button
                type="button"
                @click="editorTool = 'groupStart'"
                :class="{ 'is-selected': editorTool === 'groupStart' }"
            >
                Group Start
            </button>
        </div>
    </article>
</section>