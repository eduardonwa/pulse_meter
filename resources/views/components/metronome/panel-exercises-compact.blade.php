<template x-for="(step, index) in steps" :key="step.id ? `server-${step.id}` : mobileExerciseKey(step)">
    <li class="exercise-row exercise-row--editable" x-data="{ ...alphaTabExerciseControls(), mobileOptionsOpen: false, mobileMoving: false }"
        :class="{
            'is-active':
                isPlaying &&
                playbackSource === 'exercise' &&
                currentIndex === index
        }"
        @routine:exercise-clear-active.window="clearActiveExercise()"
        @alphatab:rendered.window="syncScoreRendered($event, $refs.alphaTab)"
        @routine:exercise-active.window="syncActiveExercise($event, index, !!step.alpha_tex)"
    >
        <x-metronome.exercise-desktop />
        <x-metronome.exercise-mobile />
        <template x-if="hasAlphaTabPattern(step)">
            <button type="button" class="button exercise-row__view" data-type="outline"
                @click.stop="openMobileExerciseView($refs.exerciseViewDialog, $refs.exerciseViewContent, $refs.exerciseDetails, $refs.alphaTab, $nextTick)">
                View exercise
            </button>
        </template>

        <dialog class="dialog-shell dialog-shell--bottom-sheet mobile-exercise-view-dialog" x-ref="exerciseViewDialog"
            :class="{ 'is-fullscreen': viewFullscreen }"
            @close="closeMobileExerciseView($refs.exerciseDetails)"
            @cancel.prevent="$refs.exerciseViewDialog.close()"
            @click.self="$refs.exerciseViewDialog.close()">
            <header class="mobile-exercise-view-dialog__header">
                <span class="mobile-exercise-view-dialog__handle" aria-hidden="true"></span>
                <strong x-text="step.name"></strong>
                <div class="mobile-exercise-view-dialog__window-actions">
                    <button type="button" class="button" data-type="icon" :aria-label="viewFullscreen ? 'Minimize exercise' : 'Expand exercise'" :title="viewFullscreen ? 'Minimize' : 'Fullscreen'" @click="toggleExerciseViewSize($refs.alphaTab, $nextTick)">
                        <x-heroicon-o-arrows-pointing-in x-show="viewFullscreen" />
                        <x-heroicon-o-arrows-pointing-out x-show="!viewFullscreen" />
                    </button>
                    <button type="button" class="button" data-type="icon" aria-label="Close exercise" title="Close" @click="$refs.exerciseViewDialog.close()">
                        <x-heroicon-o-x-mark />
                    </button>
                </div>
            </header>
            <div class="mobile-exercise-view-dialog__content" x-ref="exerciseViewContent">
                <div class="mobile-exercise-view-dialog__facts">
                    <span x-text="`${step.bpm} BPM`"></span>
                    <span x-text="getStepTimeLabel(step, index)"></span>
                    <span x-text="`${step.time_signature_numerator ?? 4}/${step.time_signature_denominator ?? 4}`"></span>
                </div>
                <div class="mobile-exercise-view-dialog__skeleton" x-show="step.alpha_tex && scoreLoading" x-cloak role="status" aria-label="Loading tablature">
                    <div class="skeleton-line skeleton-line--heading"></div>
                    <div class="skeleton-line skeleton-line--staff"></div>
                    <div class="skeleton-line skeleton-line--staff"></div>
                    <div class="skeleton-line skeleton-line--short"></div>
                    <span class="sr-only">Loading tablature</span>
                </div>
                <p x-show="!step.alpha_tex">This exercise has no tablature.</p>
                <div class="mobile-exercise-view-dialog__actions">
                    <button type="button" class="button" data-type="play-drill"
                        @click="activeExerciseIndex === index && isPlaying ? stop() : startExercise(index)"
                        x-text="activeExerciseIndex === index && isPlaying ? 'Stop' : 'Practice'"></button>
                </div>
            </div>
        </dialog>

        <!-- TAB / EXERCISE DETAILS -->
                <template x-if="step.alpha_tex">
                    <div class="exercise-row__details" x-ref="exerciseDetails">
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