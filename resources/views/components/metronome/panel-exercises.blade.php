@aware([
    'routine' => null,
    'routines' => null,
    'usesServerPersistence' => false,
])

<div class="exercises" x-show="activeTab === 'exercises'" x-cloak>
    <article class="exercises__content">
        <header class="exercises__header" x-show="practiceMode === 'routine'" x-cloak>
            <div class="exercises__routine-context">
                <span class="exercises__eyebrow">Current routine</span>
                <strong class="exercises__routine-name" x-text="activeRoutine?.name || 'My routine'"></strong>
                <span class="exercises__routine-count" x-text="steps.length + (steps.length === 1 ? ' exercise' : ' exercises')"></span>
            </div>
            @if ($usesServerPersistence && $routines->isNotEmpty())
                <button class="button exercises__routine-switch" type="button"
                    @click="$dispatch('open-practice-dialog')" aria-label="View and manage routines">
                    <span>Routines</span>
                    <x-heroicon-o-chevron-right aria-hidden="true" />
                </button>
            @endif
        </header>
        <header class="exercises__header exercises__header--playlist" x-show="practiceMode === 'playlist'" x-cloak>
            <strong x-text="activePlaylist?.name || 'Playlist'"></strong>
            @if ($usesServerPersistence && $routines->isNotEmpty())
                <button class="button exercises__routine-switch" type="button"
                    @click="$dispatch('open-practice-dialog')" aria-label="View and manage playlists">
                    <span>Playlists</span>
                    <x-heroicon-o-chevron-right aria-hidden="true" />
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