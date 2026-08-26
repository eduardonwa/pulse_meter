@aware([
    'routine' => null,
    'routines' => null,
    'usesServerPersistence' => false,
])

<div class="exercises" x-show="activeTab === 'exercises'" x-cloak>
    <article class="exercises__content">
        <header class="exercises__header">
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
            <section class="routine">
                <x-metronome.controls-dropdown>
                    <x-metronome.routine-playback-controls />
                </x-metronome.controls-dropdown>

                <ul class="routine__exercises">
                    <x-metronome.panel-exercises-compact />
                </ul>

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