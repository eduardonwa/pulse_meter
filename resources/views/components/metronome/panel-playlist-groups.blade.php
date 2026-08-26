<div class="playlist">
    <template x-if="practiceGroups.length === 0">
        <p class="playlist__empty">
            This playlist is empty. Add a routine to start practicing.
        </p>
    </template>

    <template x-for="group in practiceGroups" :key="group.is_starter ? 'starter-' + group.routine_id : 'item-' + group.playlist_item_id">
        <section class="routine__group">
            <header class="routine-group__header">
                <h3 class="routine-group__title" x-text="group.routine_name"></h3>

                <span class="badge badge--starter-routine" x-show="group.is_starter">
                    Starter
                </span>
            </header>

            <ul class="routine-group__exercises">
                <template x-for="step in group.exercises" :key="group.routine_id + '-' + step.exercise_id">
                    <li class="exercise-row exercise-row--readonly" :class="{'is-active': isPlaying && playbackSource === 'exercise' && currentIndex === step.queue_position}">
                        <div class="exercise-row__bpm">
                            <span class="exercise-row__bpm-label">bpm</span>
                            <span class="exercise-row__bpm-value" x-text="step.bpm"></span>
                        </div>

                        <div class="exercise-row__main">
                            <p class="exercise-row__name" x-text="step.name"></p>

                            <div class="exercise-row__meta">
                                <span class="exercise-row__timer"
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
                            </div>
                        </div>

                        <div class="exercise-row__play">
                            <button class="button" type="button" data-type="action-exercise"
                                :class="{'is-active': isPlaying && playbackSource === 'exercise' && currentIndex === step.queue_position}"
                                @click.stop="activeExerciseIndex === step.queue_position && isPlaying ? stop() : startExercise(step.queue_position)"
                            >
                                <x-heroicon-o-pause class="pause-icon" x-show="activeExerciseIndex === step.queue_position && isPlaying" />
                                <x-heroicon-o-play class="play-icon" x-show="!(activeExerciseIndex === step.queue_position && isPlaying)" />
                            </button>
                        </div>
                    </li>
                </template>
            </ul>
        </section>
    </template>
</div>