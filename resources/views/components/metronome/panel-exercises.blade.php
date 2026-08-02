@aware([
    'routine' => null,
    'routines' => null,
    'usesServerPersistence' => false,
])

<div class="exercises" x-show="activeTab === 'exercises'" x-cloak>
    <article class="exercises__list">
        <header class="heading-bar">
            @if (
                $usesServerPersistence
                && $routine
                && $routines->isNotEmpty()
            )
                <button type="button" class="button" data-type="icon-text" @click="$dispatch('open-routines-dialog')">
                    <span x-text="activeRoutine?.name ?? ''"></span>

                    <x-heroicon-o-arrow-top-right-on-square />
                </button>
            @endif

            <button type="button" class="add-exercise | button" data-type="icon-text" :disabled="steps.length >= maxSteps" @click="openAddStepModal()">
                <x-heroicon-o-plus-circle />
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