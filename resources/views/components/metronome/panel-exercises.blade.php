<div class="exercises" x-show="activeTab === 'exercises'" x-cloak>
    <article class="exercises__list">
        <header class="heading-bar">
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

        <ul> <x-metronome.panel-exercises-compact /> </ul>

        <x-windows.step-form-modal />

        <x-windows.advance-modal />

        <x-windows.practice-review-modal />

        <p class="total-exercises">
            <span x-text="steps.length"></span> <span>/</span> <span x-text="maxSteps"></span> exercises
        </p>
    </article>
</div>