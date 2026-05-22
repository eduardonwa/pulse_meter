<div class="exercises"
    x-show="activeTab === 'exercises'"
    @keydown.space.window.prevent="isWaitingForNextExercise && continueToNextExercise()"
    x-cloak
>
    <article class="exercises__list">
        <header class="heading-bar">
            <h2 class="header">My exercises</h2>

            <button
                type="button"
                class="add-exercise | button"
                data-type="secondary"
                :disabled="steps.length >= maxSteps"
                @click="openAddStepModal()"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Add
            </button>
        </header>

        <div class="exercises-table-wrapper">
            <div class="exercises-table">
                <div class="exercises-table__head">
                    <span></span>
                    <span class="text-center">#</span>
                    <span>Name</span>

                    <div class="bpm-field | tooltip" aria-label="Scroll to change BPM">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="height: 18px;">
                            <path fill-rule="evenodd" d="M2.24 6.8a.75.75 0 0 0 1.06-.04l1.95-2.1v8.59a.75.75 0 0 0 1.5 0V4.66l1.95 2.1a.75.75 0 1 0 1.1-1.02l-3.25-3.5a.75.75 0 0 0-1.1 0L2.2 5.74a.75.75 0 0 0 .04 1.06Zm8 6.4a.75.75 0 0 0-.04 1.06l3.25 3.5a.75.75 0 0 0 1.1 0l3.25-3.5a.75.75 0 1 0-1.1-1.02l-1.95 2.1V6.75a.75.75 0 0 0-1.5 0v8.59l-1.95-2.1a.75.75 0 0 0-1.06-.04Z" clip-rule="evenodd" />
                        </svg>
                        <span>BPM</span>
                    </div>

                    <span class="text-center">Actions</span>
                </div>

                <ul class="exercises-table__body">
                    <x-metronome.panel-exercises-list />
                </ul>
            </div>
        </div>

        <x-metronome.step-form-modal />

        <x-metronome.advance-modal />

        <x-metronome.practice-review-modal />

        <p class="total-exercises">
            <span x-text="steps.length"></span> <span>/</span> <span x-text="maxSteps"></span> exercises
        </p>
    </article>
</div>