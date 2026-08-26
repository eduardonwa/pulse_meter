<div class="playlist">
    <template x-if="practiceGroups.length === 0">
        <p class="playlist__empty">
            This playlist is empty. Add a routine to start practicing.
        </p>
    </template>

    <template x-for="group in practiceGroups" :key="group.is_starter ? 'starter-' + group.routine_id : 'item-' + group.playlist_item_id">
        <section class="routine-group">
            <header class="routine-group__header">
                <h3 class="routine-group__title" x-text="group.routine_name"></h3>

                <span class="badge badge--starter-routine" x-show="group.is_starter">
                    Starter
                </span>
            </header>

            <ul class="routine-group__exercises">
                <template x-for="step in group.exercises" :key="group.routine_id + '-' + step.exercise_id">
                    <li class="exercise-row exercise-row--readonly" x-data="alphaTabExerciseControls()"
                        :class="{'is-active': isPlaying && playbackSource === 'exercise' && currentIndex === step.queue_position}"
                        @routine:exercise-clear-active.window="clearActiveExercise()"
                        @routine:exercise-active.window="syncActiveExercise($event, step.queue_position, !!step.alpha_tex)"
                    >
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

                        <template x-if="step.alpha_tex">
                            <div class="exercise-row__details">
                                <button class="button" data-type="icon-text" type="button" :aria-expanded="detailsOpen.toString()" @click="toggleDetails($refs.alphaTab, $nextTick)">
                                    <span x-text="detailsOpen ? 'Hide exercise' : 'View exercise'">View exercise</span>
                                    <x-heroicon-s-chevron-down />
                                </button>

                                <div class="exercise-row__notation" x-show="detailsOpen" x-cloak>
                                    <div x-ref="alphaTab" data-alphatab :data-exercise-index="step.queue_position" :data-alpha-tex="btoa(step.alpha_tex)" :data-bpm="step.bpm"></div>

                                    <div class="exercise-row__audio-controls" @alphatab:playback-state.window="syncPlaybackState($event, $refs.alphaTab)">
                                        <button class="badge badge--neutral" type="button" @click="playPause($refs.alphaTab)">
                                            <span x-text="hearing ? 'Mute' : 'Listen'">Listen</span>
                                        </button>

                                        <button class="badge badge--neutral" type="button" :aria-pressed="looping.toString()" @click="toggleLoop($refs.alphaTab)">
                                            <span x-text="looping ? 'Disable loop' : 'Enable loop'">Enable loop</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </li>
                </template>
            </ul>
        </section>
    </template>
</div>