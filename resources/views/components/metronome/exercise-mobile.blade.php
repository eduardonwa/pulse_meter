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
