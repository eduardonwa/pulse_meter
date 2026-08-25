@aware([
    'routine' => null,
    'routines' => null,
    'usesServerPersistence' => false,
])

<div class="exercises" x-show="activeTab === 'exercises'" x-cloak>
    <article class="exercises__list">
        <header class="heading-bar">
            @if ($usesServerPersistence && $routines->isNotEmpty())
                <button class="button" data-type="icon-text" type="button" @click="$dispatch('open-practice-dialog')">
                    <span x-text="practiceMode === 'playlist'
                        ? activePlaylist?.name ?? ''
                        : activeRoutine?.name ?? ''
                    "></span>

                    <x-heroicon-o-arrow-top-right-on-square />
                </button>
            @endif
        </header>

        <template x-if="practiceMode === 'routine'">
            <div>
                <x-metronome.controls-dropdown>
                    <x-metronome.routine-playback-controls />
                </x-metronome.controls-dropdown>

                <ul>
                    <x-metronome.panel-exercises-compact />
                </ul>
            </div>
        </template>

        <template x-if="practiceMode === 'playlist'">
            <div>
                <x-metronome.panel-playlist-groups />
            </div>
        </template>

        <x-windows.step-form-modal />

        <x-windows.advance-modal />

        <x-windows.practice-review-modal />

        <div class="footer-bar">
            <button class="add-exercise | button" data-type="icon-text" type="button"
                x-show="canManageExercises"
                :disabled="steps.length >= maxSteps"
                @click="openAddStepModal()"
            >
                <x-heroicon-o-plus-circle />
                Add Exercise
            </button>
        </div>

        <p class="total-exercises" x-show="canManageExercises">
            <span x-text="steps.length"></span>
            <span>/</span>
            <span x-text="maxSteps"></span>
            exercises
        </p>
    </article>
</div>