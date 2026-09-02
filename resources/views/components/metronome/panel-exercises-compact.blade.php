<template x-for="(step, index) in steps" :key="index">
    <li class="exercise-row exercise-row--editable" x-data="alphaTabExerciseControls()"
        :class="{
            'is-active':
                isPlaying &&
                playbackSource === 'exercise' &&
                currentIndex === index
        }"
        @routine:exercise-clear-active.window="clearActiveExercise()"
        @routine:exercise-active.window="syncActiveExercise($event, index, !!step.alpha_tex)"
    >
        <!-- BPM -->
        <div class="exercise-row__bpm">
            <span class="exercise-row__bpm-label">bpm</span>

            <x-inputs.number-picker
                class="exercise-row__bpm-value"
                options="bpmOptions"
                model="step.bpm"
                format="(value) => value"
                after-change="updateExerciseBpm(index, value)"
                :controls="true"
                decrease-label="Decrease BPM"
                increase-label="Increase BPM"
                hint="Scroll to change BPM"
            />
        </div>

        <!-- EXERCISE INFO -->
        <div class="exercise-row__main">
            <input class="exercise-row__name" type="text" x-model="step.name" @click.stop @input="updateExerciseName(index, $event.target.value)">
            <div class="exercise-row__meta">
                <span class="exercise-row__timer" x-text="getStepTimeLabel(step, index)"
                    :class="{'is-counting':
                            isPlaying &&
                            playbackSource === 'exercise' &&
                            currentIndex === index &&
                            step.mode === 'timer',

                        'badge': step.mode === 'classic'
                    }"
                ></span>

                <div class="exercise-actions" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="button" data-type="action-exercise" :aria-expanded="open.toString()" @click.stop="open = !open">
                        <x-heroicon-o-ellipsis-horizontal />
                    </button>

                    <div class="exercise-actions__menu" x-show="open" x-cloak>
                        <button type="button" class="button" data-type="icon-text"
                            @click.stop="open = false; openEditStepModal(index);"
                        >
                            <x-heroicon-s-pencil />
                            Edit
                        </button>

                        <button type="button" class="button" data-type="icon-text"
                            :disabled="steps.length <= 1"
                            @click.stop="open = false; openDeleteStepModal(index);"
                        >
                            <x-heroicon-s-trash />
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PLAY -->
        <div class="exercise-row__play">
            <button class="button" type="button" data-type="action-exercise"
                :class="{
                    'is-active':
                        isPlaying &&
                        playbackSource === 'exercise' &&
                        currentIndex === index
                }"
                @click.stop="
                    activeExerciseIndex === index && isPlaying
                        ? stop()
                        : startExercise(index)
                "
            >
                <x-heroicon-o-stop
                    class="pause-icon"
                    x-show="activeExerciseIndex === index && isPlaying"
                />

                <x-heroicon-o-play
                    class="play-icon"
                    x-show="!(activeExerciseIndex === index && isPlaying)"
                />
            </button>
        </div>
     
        <!-- TAB / EXERCISE DETAILS -->
        <template x-if="step.alpha_tex">
            <div class="exercise-row__details">
                <button class="button" data-type="icon-text" type="button"
                    :aria-expanded="detailsOpen.toString()"
                    @click="toggleDetails($refs.alphaTab, $nextTick)"
                >
                    <span x-text=" detailsOpen ? 'Hide exercise' : 'View exercise'">
                        View exercise
                    </span>

                    <x-heroicon-s-chevron-down />
                </button>

                <div class="exercise-row__notation" x-show="detailsOpen" x-cloak>
                    <div x-ref="alphaTab"
                        data-alphatab
                        :data-exercise-index="index"
                        :data-alpha-tex="btoa(step.alpha_tex)"
                        :data-bpm="step.bpm"
                    ></div>

                    <div class="exercise-row__audio-controls" @alphatab:playback-state.window="syncPlaybackState($event, $refs.alphaTab)">
                        <button class="badge badge--neutral" type="button"
                            @click="
                                hearing
                                    ? stopPlayback()
                                    : playPause($refs.alphaTab)
                            "
                        >
                            <span x-text="hearing ? 'Stop' : 'Listen'">
                                Listen
                            </span>
                        </button>

                        <button class="badge badge--neutral" type="button" :aria-pressed="looping.toString()" @click="toggleLoop($refs.alphaTab)">
                            <span x-text=" looping ? 'Disable loop' : 'Enable loop'">
                                Enable loop
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </li>
</template>