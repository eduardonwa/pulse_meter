@aware([
    'routine' => null,
    'routines' => null,
    'usesServerPersistence' => false,
])

<div class="exercises" x-show="activeTab === 'exercises'" x-cloak>
    <article class="exercises__content">
        <header class="exercises__header">
            <div class="exercises__routine-context">
                <strong
                    class="exercises__routine-name"
                    x-text="activeRoutine?.name || 'Free Exercises'"
                ></strong>
            </div>
            <div class="exercises__actions">
                <div class="exercises__start" x-show="practiceMode === 'routine'" x-cloak>
                    <button
                        class="button uppercase"
                        data-type="play-metronome"
                        type="button"
                        :disabled="steps.length === 0 && !isPlaying && !isWaitingForNextExercise"
                        :data-state="isPlaying || isWaitingForNextExercise ? 'stop' : 'start'"
                        @click="toggleRoutinePlayback()"
                    >
                        <span x-text="isPlaying || isWaitingForNextExercise ? 'Stop Routine' : 'Start Routine'">Start Routine</span>
                    </button>
                </div>
                @if ($usesServerPersistence && $routines->isNotEmpty())
                    <button class="button" data-type="icon-text" type="button" @click="$dispatch('open-practice-dialog')">
                        <span>Routines</span>
                        <x-heroicon-o-chevron-right />
                    </button>
                @endif
            </div>
        </header>

        <template x-if="practiceMode === 'routine'">
            <section class="routine">
                <x-metronome.controls-dropdown>
                    <x-metronome.routine-playback-controls />
                </x-metronome.controls-dropdown>

                <ul class="routine__exercises">
                    <x-metronome.panel-exercises-compact />
                </ul>

                <div class="exercise-undo" x-show="deletedExerciseUndo !== null" x-cloak role="status" aria-live="polite">
                    <span>Exercise deleted</span>
                    <button type="button" @click="undoDeletedExercise()" :disabled="exerciseActionBusy">Undo</button>
                </div>

                <div class="routine__footer">
                    <button class="button" data-type="icon-text" type="button" :disabled="steps.length >= maxSteps" @click="openAddStepModal()">
                        <x-heroicon-o-plus-circle />
                        Add Exercise
                    </button>
                </div>

                <p class="routine__total">
                    <span x-text="steps.length"></span>
                    <span>/</span>
                    <span x-text="maxSteps"></span>
                    exercises
                </p>
            </section>
        </template>

        <template x-if="practiceMode === 'playlist'">
            <div>
                <x-metronome.panel-playlist-groups />
            </div>
        </template>

        <x-windows.step-form-modal />
        <x-windows.advance-modal />
        <x-windows.practice-review-modal />
    </article>
</div>