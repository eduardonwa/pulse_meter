<template x-for="(step, index) in steps" :key="step.id ? `server-${step.id}` : mobileExerciseKey(step)">
    <li class="exercise-row exercise-row--editable" x-data="{ ...alphaTabExerciseControls(), mobileOptionsOpen: false, mobileMoving: false }"
        :class="{
            'is-active':
                isPlaying &&
                playbackSource === 'exercise' &&
                currentIndex === index
        }"
        @routine:exercise-clear-active.window="clearActiveExercise()"
        @routine:exercise-active.window="syncActiveExercise($event, index, !!step.alpha_tex)"
    >
        <x-metronome.exercise-desktop />
        <x-metronome.exercise-mobile />

        <dialog class="dialog-shell mobile-exercise-view-dialog" x-ref="exerciseViewDialog"
            @close="closeMobileExerciseView($refs.exerciseDetails)"
            @cancel.prevent="$refs.exerciseViewDialog.close()"
            @click.self="$refs.exerciseViewDialog.close()">
            <header class="mobile-exercise-view-dialog__header">
                <strong x-text="step.name"></strong>
                <button type="button" class="button" data-type="icon" aria-label="Close exercise" @click="$refs.exerciseViewDialog.close()">
                    <x-heroicon-o-x-mark />
                </button>
            </header>
            <div class="mobile-exercise-view-dialog__content" x-ref="exerciseViewContent">
                <div class="mobile-exercise-view-dialog__facts">
                    <span x-text="`${step.bpm} BPM`"></span>
                    <span x-text="getStepTimeLabel(step, index)"></span>
                    <span x-text="`${step.time_signature_numerator ?? 4}/${step.time_signature_denominator ?? 4}`"></span>
                </div>
                <p x-show="!step.alpha_tex">This exercise has no tablature.</p>
            </div>
        </dialog>

        <!-- TAB / EXERCISE DETAILS -->
                <template x-if="step.alpha_tex">
                    <div class="exercise-row__details" x-ref="exerciseDetails">
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
                                    <span x-text="hearing ? 'Stop' : 'Preview'">
                                        Preview
                                    </span>
                                </button>
        
                            </div>
                        </div>
                    </div>
                </template>
    </li>
</template>