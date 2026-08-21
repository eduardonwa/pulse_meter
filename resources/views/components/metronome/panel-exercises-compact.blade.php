<template x-for="(step, index) in steps" :key="index">
    <li class="exercise-layout" x-data="{ detailsOpen: false }"
        :class="{
            'is-active':
                isPlaying
                && playbackSource === 'exercise'
                && currentIndex === index
        }"

        @routine:exercise-clear-active.window="
            detailsOpen = false
        "

        @routine:exercise-active.window="
            const isActive = $event.detail.index === index;
            detailsOpen = isActive && !!step.alpha_tex;
        "
    >
        <div class="exercise-layout__bpm">
            <x-inputs.number-picker
                class="exercise-row__bpm"
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
        
        <div class="exercise-layout__info">
            <section class="summary">
                <input
                    class="name"
                    type="text"
                    x-model="step.name"
                    @input="
                        updateExerciseName(
                            index,
                            $event.target.value
                        )
                    "
                    @click.stop
                >
                
                <div class="bottom">
                    <button
                        type="button"
                        class="button"
                        data-type="action-exercise"
                        @click.stop="openEditStepModal(index)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="clr-neutral-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="button"
                        data-type="action-exercise"
                        @click.stop="openDeleteStepModal(index)"
                        :disabled="steps.length <= 1"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="error">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </button>
                    
                    <span class="timer"
                        :class="{ 
                            'is-counting': isPlaying && playbackSource === 'exercise' && currentIndex === index && step.mode === 'timer',
                            'badge': step.mode === 'classic'
                        }"
                        x-text="getStepTimeLabel(step, index)"
                    ></span>
                </div>
            </section>

            <section class="play-exercise">
                <button
                    type="button"
                    class="exercise-row__playback | button"
                    data-type="action-exercise"
                    :class="{ 'is-active': isPlaying && playbackSource === 'exercise' && currentIndex === index }"
                    @click.stop="activeExerciseIndex === index && isPlaying ? stop() : startExercise(index)"
                >
                    <x-heroicon-o-pause-circle  class="pause-icon" x-show="activeExerciseIndex === index && isPlaying" />
                    <x-heroicon-o-play-circle class="play-icon" x-show="!(activeExerciseIndex === index && isPlaying)" />
                </button>
            </section>
        </div>

        <template x-if="step.alpha_tex">
            <div>
                <button class="routine-player__exercise-details-toggle button" type="button" :aria-expanded="detailsOpen.toString()"
                    @click="
                        detailsOpen = !detailsOpen;

                        if (detailsOpen) {
                            $nextTick(() => {
                                window.dispatchEvent(
                                    new CustomEvent('alphatab:mount', {
                                        detail: {
                                            element: $refs.alphaTab
                                        }
                                    })
                                );
                            });
                        } else {
                            window.dispatchEvent(
                                new Event('alphatab:stop')
                            );
                        }
                    "
                >
                    <span
                        x-text="
                            detailsOpen
                                ? 'Hide exercise'
                                : 'View exercise'
                        "
                    >
                        View exercise
                    </span>
                </button>

                <div class="routine-player__exercise-details" x-show="detailsOpen" x-cloak>
                    <div
                        x-ref="alphaTab"
                        data-alphatab
                        :data-exercise-index="index"
                        :data-alpha-tex="btoa(step.alpha_tex)"
                        :data-bpm="step.bpm"
                    ></div>

                    <div class="routine-player__exercise-audio-controls"
                        x-data="{
                            looping: false,
                            hearing: false
                        }"

                        @alphatab:playback-state.window="
                            if ($event.detail.element === $refs.alphaTab) {
                                hearing = $event.detail.playing;
                            }
                        "
                    >
                        <button class="badge badge--neutral" type="button"
                            @click="
                                window.dispatchEvent(
                                    new CustomEvent(
                                        'alphatab:play-pause',
                                        {
                                            detail: {
                                                element: $refs.alphaTab
                                            }
                                        }
                                    )
                                );
                            "
                        >
                            <span x-text="hearing ? 'Mute' : 'Listen'">
                                Listen
                            </span>
                        </button>

                        <button class="badge badge--neutral" type="button" :aria-pressed="looping.toString()"
                            @click="
                                looping = !looping;
                                window.dispatchEvent(
                                    new CustomEvent(
                                        'alphatab:toggle-loop',
                                        {
                                            detail: {
                                                element: $refs.alphaTab
                                            }
                                        }
                                    )
                                );
                            "
                        >
                            <span x-text="looping ? 'Disable loop' : 'Enable loop'">
                                Enable loop
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </li>
</template>