<section class="metronome__main">
    <h2 class="current-beat" x-text="currentBeat"></h2>
    
    <article class="beats">
        <template x-for="beat in beatsPerMeasure" :key="beat">
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

        <input class="tempo-range"
            type="range"
            min="30"
            max="400"
            value="100"
            x-model.number="metronome.bpm"
            @change="handleBpmChange()"
        >

        <div class="current-exercise-readout" x-show="activeTab === 'exercises' && activeExerciseIndex !== null">
            <span x-text="getActiveExerciseName()"></span>

            <span
                class="current-exercise-readout__time"
                :class="{ 'is-counting': isPlaying && steps[activeExerciseIndex]?.mode === 'timer' }"
                x-text="getActiveExerciseTimeLabel()"
            ></span>
        </div>
    </div>

    <div class="play">
        <button class="button uppercase"
            data-type="play-metronome"
            :data-state="isPlaying ? 'stop' : 'start'"
            @click="toggle()"
        >
            <span class="play-label" x-text="isPlaying ? 'Stop' : 'Start'"></span>
        </button>
    </div>
</section>