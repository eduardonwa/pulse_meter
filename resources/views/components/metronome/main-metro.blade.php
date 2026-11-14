<section class="metronome__main" x-data="{ showStickyDivider: window.scrollY >= 300 }" :class="{ 'is-scrolled': showStickyDivider }" @scroll.window="showStickyDivider = window.scrollY >= 300">
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

    <h2 class="current-beat" x-text="currentBeat">1</h2>

    <article class="beats">
        <template x-for="beat in getPlaybackBeatCount()" :key="`${metronome.mode}-${beat}`">
            <div class="beat-mark" x-text="beat" :class="{ 'is-active': currentBeat === beat }"></div>
        </template>
    </article>

    <div class="tempo-control">
        <div class="tempo-display">
            <label for="bpm">
                <span class="tempo-value" x-text="`${metronome.bpm}`">100</span>
                <span class="tempo-unit uppercase">bpm</span>
            </label>
        </div>

        <div
            class="tempo-slider"
            :style="{ '--progress': (metronome.bpm - 30) / 370 * 100 }"
        >
            <div class="tempo-slider__track" aria-hidden="true">
                <div class="tempo-slider__active"></div>
                <div class="tempo-slider__inactive"></div>
            </div>

            <input
                class="tempo-range"
                type="range"
                id="bpm"
                min="30"
                max="400"
                step="1"
                value="100"
                x-model.number="metronome.bpm"
                @change="handleBpmChange()"
            >
        </div>

        <div class="tempo-step-controls">
            <button
                type="button"
                class="tempo-step"
                aria-label="Decrease tempo by 1 BPM"
                :disabled="metronome.bpm <= 30"
                @click="adjustBpm(-1)"
            >−</button>

            <button
                type="button"
                class="tempo-step"
                aria-label="Increase tempo by 1 BPM"
                :disabled="metronome.bpm >= 400"
                @click="adjustBpm(1)"
            >+</button>
        </div>

        <div
            class="current-exercise-readout"
            x-show="activeTab === 'exercises' && activeExerciseIndex !== null"
        >
            <span x-text="getActiveExerciseName()"></span>

            <span
                class="current-exercise-readout__time"
                :class="{ 'is-counting': isPlaying && steps[activeExerciseIndex]?.mode === 'drill' }"
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
            <span class="play-label" x-text="isPlaying ? 'Stop' : 'Start'">
                Start
            </span>
        </button>
    </div>
</section>