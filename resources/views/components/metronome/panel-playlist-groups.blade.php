<div class="playlist-practice">
    <template x-if="practiceGroups.length === 0">
        <p class="playlist-practice__empty">
            This playlist has no routines yet.
        </p>
    </template>

    <template
        x-for="group in practiceGroups"
        :key="
            group.is_starter
                ? 'starter-' + group.routine_id
                : 'item-' + group.playlist_item_id
        "
    >
        <section class="playlist-practice__group">
            <header class="playlist-practice__heading">
                <h3 x-text="group.routine_name"></h3>

                <span
                    class="badge"
                    x-show="group.is_starter"
                >
                    Starter Routine
                </span>
            </header>

            <ul class="playlist-practice__exercises">
                <template
                    x-for="step in group.exercises"
                    :key="
                        group.routine_id
                        + '-'
                        + step.exercise_id
                    "
                >
                    <li
                        class="exercise-layout"
                        :class="{
                            'is-active':
                                isPlaying
                                && activeSessionType === 'exercise'
                                && currentIndex === step.queue_position
                        }"
                    >
                        <div class="exercise-layout__bpm">
                            <span
                                class="exercise-row__bpm"
                                x-text="step.bpm"
                            ></span>
                        </div>

                        <div class="exercise-layout__info">
                            <section class="summary">
                                <input
                                    class="name"
                                    type="text"
                                    :value="step.name"
                                    readonly
                                >

                                <div class="bottom">
                                    <span
                                        class="timer"
                                        :class="{
                                            'is-counting':
                                                isPlaying
                                                && activeSessionType === 'exercise'
                                                && currentIndex === step.queue_position
                                                && step.mode === 'timer',

                                            'badge':
                                                step.mode === 'classic'
                                        }"
                                        x-text="
                                            getStepTimeLabel(
                                                step,
                                                step.queue_position
                                            )
                                        "
                                    ></span>
                                </div>
                            </section>

                            <section class="play-exercise">
                                <button
                                    type="button"
                                    class="exercise-row__playback | button"
                                    data-type="action-exercise"
                                    :class="{
                                        'is-active':
                                            isPlaying
                                            && activeSessionType === 'exercise'
                                            && currentIndex === step.queue_position
                                    }"
                                    @click.stop="
                                        activeExerciseIndex === step.queue_position
                                        && isPlaying
                                            ? stop()
                                            : startExercise(
                                                step.queue_position
                                            )
                                    "
                                >
                                    <svg
                                        x-show="
                                            activeExerciseIndex === step.queue_position
                                            && isPlaying
                                        "
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="pause-icon"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                        />
                                    </svg>

                                    <svg
                                        x-show="
                                            !(
                                                activeExerciseIndex === step.queue_position
                                                && isPlaying
                                            )
                                        "
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="play-icon"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                        />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z"
                                        />
                                    </svg>
                                </button>
                            </section>
                        </div>
                    </li>
                </template>
            </ul>
        </section>
    </template>
</div>