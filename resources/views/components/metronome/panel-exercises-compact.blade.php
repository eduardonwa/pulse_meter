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
     
        <!-- Touch-first routine card. Desktop keeps the existing compact row. -->
        <div class="mobile-exercise" :class="{ 'mobile-exercise--open': mobileExpandedIndex === index }">
            <button type="button" class="mobile-exercise__summary"
                :aria-expanded="(mobileExpandedIndex === index).toString()"
                @click="mobileExpandedIndex = mobileExpandedIndex === index ? null : index">
                <span class="mobile-exercise__heading">
                    <small x-text="`EXERCISE ${String(index + 1).padStart(2, '0')}`"></small>
                    <strong x-text="step.name"></strong>
                    <span class="mobile-exercise__short" x-show="mobileExpandedIndex !== index"
                        x-text="`${step.bpm} BPM · ${getStepTimeLabel(step, index)} · ${step.time_signature_numerator ?? 4}/${step.time_signature_denominator ?? 4}`"></span>
                </span>
                <span class="mobile-exercise__chevron" aria-hidden="true" x-text="mobileExpandedIndex === index ? '⌃' : '⌄'"></span>
            </button>
            <div class="mobile-exercise__expanded" x-show="mobileExpandedIndex === index" x-cloak>
                <div class="mobile-exercise__stats">
                    <div><small>BPM</small><strong x-text="step.bpm"></strong></div>
                    <div><small>LENGTH</small><strong x-text="getStepTimeLabel(step, index)"></strong></div>
                    <div><small>METER</small><strong x-text="`${step.time_signature_numerator ?? 4}/${step.time_signature_denominator ?? 4}`"></strong></div>
                </div>
                <div class="mobile-exercise__tempo">
                    <button type="button" aria-label="Decrease BPM" @click.stop="updateExerciseBpm(index, Math.max(30, Number(step.bpm) - 1))">−</button>
                    <span>Adjust tempo</span>
                    <button type="button" aria-label="Increase BPM" @click.stop="updateExerciseBpm(index, Math.min(400, Number(step.bpm) + 1))">+</button>
                </div>
                <div class="mobile-exercise__actions">
                    <button type="button" class="mobile-exercise__practice"
                        @click.stop="activeExerciseIndex === index && isPlaying ? stop() : startExercise(index)">
                        <span x-text="activeExerciseIndex === index && isPlaying ? 'Stop' : 'Practice'"></span>
                    </button>
                    <button type="button" aria-label="Edit exercise" @click.stop="openEditStepModal(index)">
                        <x-heroicon-o-pencil-square />
                    </button>
                    <button type="button" aria-label="Exercise options" :aria-expanded="mobileOptionsOpen.toString()"
                        @click.stop="mobileOptionsOpen = true; mobileMoving = false">
                        <x-heroicon-o-ellipsis-horizontal />
                    </button>
                </div>
            </div>
            <template x-teleport="body">
                <div class="mobile-exercise-sheet" x-show="mobileOptionsOpen" x-cloak
                    @keydown.escape.window="mobileOptionsOpen = false; mobileMoving = false">
                    <div class="mobile-exercise-sheet__backdrop" @click="mobileOptionsOpen = false; mobileMoving = false"></div>
                    <div class="mobile-exercise-sheet__panel" role="dialog" aria-modal="true" aria-label="Exercise options">
                        <div class="mobile-exercise-sheet__handle"></div>
                        <div class="mobile-exercise-sheet__title">
                            <div><strong x-text="mobileMoving ? 'Move exercise' : 'Exercise options'"></strong>
                                <small x-text="step.name"></small></div>
                            <button type="button" aria-label="Close exercise options" @click="mobileOptionsOpen = false; mobileMoving = false">×</button>
                        </div>
                        <template x-if="!mobileMoving">
                            <div class="mobile-exercise-sheet__list">
                                <button type="button" :disabled="steps.length >= maxSteps || exerciseActionBusy || isPlaying || isWaitingForNextExercise"
                                    @click="mobileOptionsOpen = false; duplicateMobileExercise(index)">Duplicate</button>
                                <button type="button" :disabled="exerciseActionBusy || isPlaying || isWaitingForNextExercise" @click="mobileMoving = true">Move</button>
                                <button type="button" class="mobile-exercise-sheet__danger" :disabled="steps.length <= 1"
                                    @click="mobileOptionsOpen = false; mobileMoving = false; $nextTick(() => requestAnimationFrame(() => openDeleteStepModal(index)))">Delete</button>
                            </div>
                        </template>
                        <template x-if="mobileMoving">
                            <div class="mobile-exercise-sheet__list">
                                <p x-text="`Position ${index + 1} of ${steps.length}`"></p>
                                <div class="mobile-exercise-sheet__directions">
                                    <button type="button" :disabled="index === 0 || exerciseActionBusy || isPlaying || isWaitingForNextExercise"
                                        @click="moveMobileExercise(index, -1)">Move up</button>
                                    <button type="button" :disabled="index === steps.length - 1 || exerciseActionBusy || isPlaying || isWaitingForNextExercise"
                                        @click="moveMobileExercise(index, 1)">Move down</button>
                                </div>
                                
                                <button type="button" @click="mobileOptionsOpen = false; mobileMoving = false">Done</button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
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