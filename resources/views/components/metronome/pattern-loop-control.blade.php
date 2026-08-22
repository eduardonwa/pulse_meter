<div {{ $attributes->class(['loop-control']) }}>
    <span class="label">
        Loop patterns
    </span>

    <div class="loop-control__toggle">
        <button
            type="button"
            class="option"
            :class="{
                'option--active': loopAllExercises
            }"
            :aria-pressed="loopAllExercises.toString()"
            @click="setPatternLoop(true)"
        >
            On
        </button>

        <button
            type="button"
            class="option"
            :class="{
                'option--active': !loopAllExercises
            }"
            :aria-pressed="(!loopAllExercises).toString()"
            @click="setPatternLoop(false)"
        >
            Off
        </button>
    </div>
</div>