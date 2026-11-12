<div class="mobile-exercise" :class="{ 'mobile-exercise--open': mobileExpandedIndex === index }">
    <button type="button" class="mobile-exercise__summary" :aria-expanded="(mobileExpandedIndex === index).toString()"
        @click="mobileExpandedIndex = mobileExpandedIndex === index ? null : index; $nextTick(() => $dispatch('picker:sync'))">
                
        <span class="mobile-exercise__heading">
            <strong class="name" x-text="step.name"></strong>
            <span class="short" x-show="mobileExpandedIndex !== index"
                x-text="`${step.bpm} BPM · ${getStepTimeLabel(step, index)} · ${step.time_signature_numerator ?? 4}/${step.time_signature_denominator ?? 4}`"></span>
        </span>

        <x-heroicon-o-chevron-down class="mobile-exercise__chevron" aria-hidden="true" />
    </button>
    
    {{-- CARD CONTENT --}}
    <div class="mobile-exercise__expanded" x-show="mobileExpandedIndex === index" x-cloak>
        <div class="mobile-exercise__metrics">
            <div class="mobile-exercise__metric mobile-exercise__metric--duration">
                <small>DURATION</small>
                <strong x-text="getStepTimeLabel(step, index)"></strong>
            </div>

            <div class="mobile-exercise__metric mobile-exercise__metric--meter">
                <small>METER</small>
                <strong x-text="`${step.time_signature_numerator ?? 4}/${step.time_signature_denominator ?? 4}`"></strong>
            </div>

            <div class="mobile-exercise__metric mobile-exercise__metric--bpm">
                <div
                    class="mobile-exercise__bpm-wheel-container"
                    x-data="{ displayBpm: Number(step.bpm) }"
                    x-effect="displayBpm = Number(step.bpm)"
                    @picker:preview.stop="displayBpm = Number($event.detail.value)"
                >
                    <x-inputs.number-picker
                        class="mobile-exercise__bpm-wheel"
                        options="bpmOptions"
                        model="step.bpm"
                        format="(value) => value"
                        after-change="updateExerciseBpm(index, value)"
                        hint="Scroll to adjust exercise BPM"
                        :live-preview="true"
                    />
                    <strong
                        class="mobile-exercise__bpm-centered-value"
                        aria-hidden="true"
                        x-text="displayBpm"
                    ></strong>
                </div>
                <small class="mobile-exercise__bpm-unit">BPM</small>
            </div>
        </div>

        <div class="mobile-exercise__actions">
            <button class="button" data-type="play-drill" type="button" class="mobile-exercise__practice"
                @click.stop="startMobileExercisePractice(index, step, $refs.exerciseViewDialog, $refs.exerciseViewContent, $refs.exerciseDetails, $refs.alphaTab, $nextTick)">
                <span x-text="activeExerciseIndex === index && isPlaying ? 'Stop' : 'Practice'"></span>
            </button>

            <button class="button" data-type="icon" type="button" aria-label="Edit exercise" @click.stop="openEditStepModal(index)">
                <x-heroicon-o-pencil-square />
            </button>

            <button class="button" data-type="icon" type="button" aria-label="Exercise options" :aria-expanded="mobileOptionsOpen.toString()"
                @click.stop="mobileOptionsOpen = true; mobileMoving = false">
                <x-heroicon-o-ellipsis-horizontal />
            </button>
        </div>
    </div>
    
    {{-- BOTTOM SHEET --}}
    <template x-teleport="body">
        <div class="mobile-exercise-sheet" x-show="mobileOptionsOpen" x-cloak @keydown.escape.window="mobileOptionsOpen = false; mobileMoving = false">
            <div class="mobile-exercise-sheet__backdrop" @click="mobileOptionsOpen = false; mobileMoving = false"></div>
            
            <div class="mobile-exercise-sheet__panel" role="dialog" aria-modal="true" aria-label="Exercise options">
                <div class="mobile-exercise-sheet__handle"></div>
                
                <div class="mobile-exercise-sheet__title">
                    <div>
                        <strong x-text="mobileMoving ? 'Move exercise' : 'Exercise options'"></strong>
                        <small x-text="step.name"></small>
                    </div>

                    <button class="button" data-type="icon" type="button" aria-label="Close exercise options" @click="mobileOptionsOpen = false; mobileMoving = false">
                        <x-heroicon-o-x-mark />
                    </button>
                </div>

                <template x-if="!mobileMoving">
                    <div class="mobile-exercise-sheet__list">
                        <button type="button" :disabled="steps.length >= maxSteps || exerciseActionBusy || isPlaying || isWaitingForNextExercise || (hasAlphaTabPattern(step) && !canCreateTablatures)"
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
                            <button class="button" data-type="icon" type="button" aria-label="Move up" :disabled="index === 0 || exerciseActionBusy || isPlaying || isWaitingForNextExercise" @click="moveMobileExercise(index, -1)">
                                <x-heroicon-o-arrow-up />
                            </button>

                            <button class="button" data-type="icon" type="button" aria-label="Move down" :disabled="index === steps.length - 1 || exerciseActionBusy || isPlaying || isWaitingForNextExercise" @click="moveMobileExercise(index, 1)">
                                <x-heroicon-o-arrow-down />
                            </button>
                        </div>
                        
                        <button type="button" @click="mobileOptionsOpen = false; mobileMoving = false">Done</button>
                    </div>
                </template>
            </div>
        </div>
    </template>
</div>