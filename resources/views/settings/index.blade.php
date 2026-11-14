<x-layouts.dorelog title="Settings | Dorelog" description="Configure your practice preferences.">
    <main class="container practice-settings-page"
        x-data="practiceSettings({
            importPreferenceUrl: @js(auth()->user()?->hasProAccess() ? route('practice-settings.free-exercise-import-prompt') : null),
            initialImportPreference: @js((bool) (auth()->user()?->ask_before_importing_free_exercises ?? true))
        })">
        <h1 class="heading-3">Settings</h1>

        <div class="settings">
            <div class="setting-option group">
                <div class="group__header">
                    <h2 class="group__title">Exercises</h2>
                    <p class="group__hint">Settings for your practice flow</p>
                </div>
                <div class="group__body group__body--stacked">
                    <label class="auto-advance-field">
                        <input type="checkbox" :checked="autoAdvance" @change="setAutoAdvance($event.target.checked)">
                        <span>Auto-advance to next exercise</span>
                    </label>
                    @if (auth()->user()?->hasProAccess())
                        <label class="free-exercise-import-field">
                            <input type="checkbox"
                                :checked="askBeforeImportingFreeExercises"
                                :disabled="importPreferenceBusy"
                                @change="setImportPreference($event.target.checked)">
                            <span>Ask before importing Free exercises after signing in</span>
                        </label>
                    @endif
                </div>
            </div>

            <div class="setting-option group">
                <div class="group__header">
                    <h2 class="group__title">Click sound profile</h2>
                    <p class="group__hint">Choose the sound used for the metronome click</p>
                </div>
                <div class="group__body">
                    <select class="select-click-profile" x-model="clickProfile" @change="setClickProfile($event.target.value)">
                        <option value="ableton">Ableton</option>
                        <option value="cubase">Cubase</option>
                    </select>
                </div>
            </div>

            <div class="setting-option group mobile-audio-help">
                <div class="group__header">
                    <h2 class="group__title">No sound?</h2>
                    <p class="fs-300">
                        If the metronome goes silent after switching apps or tabs on your mobile device,
                        try reloading the page or restarting your browser.
                    </p>
                </div>
            </div>

            <div class="setting-option group">
                <div class="group__header">
                    <h2 class="group__title">DANGER ZONE</h2>
                    <p class="fs-300">These actions can't be undone</p>
                </div>
                <div class="group__body">
                    <button type="button" class="button" data-type="danger-zone" @click="showResetAppModal = true">
                        Reset local settings and exercises
                    </button>
                </div>
            </div>
        </div>
        <p role="status" aria-live="polite" x-text="feedback" x-show="feedback"></p>

        <div class="modal-shell" x-show="showResetAppModal" x-trap.noscroll="showResetAppModal"
            @keydown.escape.window="showResetAppModal = false" @click.self="showResetAppModal = false" x-cloak>
            <div class="modal-panel" data-type="danger" @click.stop>
                <div class="heading">
                    <h2>Reset app?</h2>
                    <p class="fs-300">This will delete your locally stored exercises and recent sessions.</p>
                </div>
                <div class="modal-panel__actions">
                    <button class="button" type="button" data-type="outline" @click="showResetAppModal = false">Cancel</button>
                    <button class="button" type="button" data-type="outline" data-variant="danger" @click="clearAllAppStorage()">Reset</button>
                </div>
            </div>
        </div>
    </main>
</x-layouts.dorelog>
