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