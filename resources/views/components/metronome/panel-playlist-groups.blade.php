<div class="playlist-practice">
    <template x-if="practiceGroups.length === 0">
        <p class="playlist-practice__empty">
            This playlist is empty. Add a routine to start practicing.
        </p>
    </template>

    <template x-for="group in practiceGroups" :key="group.is_starter ? 'starter-' + group.routine_id : 'item-' + group.playlist_item_id">
        <section class="playlist-practice__group">
            <header class="playlist-practice__heading">
                <h3 x-text="group.routine_name"></h3>

                <span class="badge badge--starter-routine" x-show="group.is_starter">
                    Starter Routine
                </span>
            </header>

            <ul class="playlist-practice__exercises">
                <template x-for="step in group.exercises" :key="group.routine_id + '-' + step.exercise_id">
                    <li class="exercise-layout"
                        :class="{
                            'is-active':
                                isPlaying
                                    && playbackSource === 'exercise'
                                    && currentIndex === step.queue_position
                        }"
                    >
                        <div class="exercise-layout__bpm">
                            <span class="exercise-row__bpm" x-text="step.bpm"></span>
                        </div>

                        <div class="exercise-layout__info">
                            <section class="summary">
                                <p class="exercise-name" x-text="step.name"></p>

                                <span class="timer"
                                    :class="{
                                        'is-counting':
                                            isPlaying
                                                && playbackSource === 'exercise'
                                                && currentIndex === step.queue_position
                                                && step.mode === 'timer',

                                        'badge': step.mode === 'classic'
                                    }"
                                    x-text="getStepTimeLabel(step, step.queue_position)"
                                ></span>
                            </section>

                            <section class="play-exercise">
                                <button class="exercise-row__playback | button" type="button" data-type="action-exercise"
                                    :class="{
                                        'is-active': isPlaying
                                            && playbackSource === 'exercise'
                                            && currentIndex === step.queue_position
                                    }"
                                    @click.stop="
                                        activeExerciseIndex === step.queue_position
                                        && isPlaying
                                            ? stop()
                                            : startExercise(step.queue_position)
                                    "
                                >
                                    <x-heroicon-o-pause-circle class="pause-icon" x-show="activeExerciseIndex === step.queue_position && isPlaying" />
                                    <x-heroicon-o-play-circle class="play-icon" x-show="!(activeExerciseIndex === step.queue_position && isPlaying)"/>
                                </button>
                            </section>
                        </div>
                    </li>
                </template>
            </ul>
        </section>
    </template>
</div>