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
                <template x-if="!step.alpha_tex">
                    <span class="exercise-row__time-signature"
                        x-text="
                            `${step.time_signature_numerator ?? 4}/${step.time_signature_denominator ?? 4}`
                        "
                    ></span>
                </template>

                <span class="exercise-row__duration" x-text="getStepTimeLabel(step, index)"
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
     
        