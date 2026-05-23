<div class="settings" x-show="activeTab === 'settings'" x-cloak>     
    <div class="setting-option group">
        <div class="group__header">
            <h2 class="group__title">Exercises</h2>
            <p class="group__hint">
                Settings for your practice flow
            </p>
        </div>

        <div class="group__body">
            <label class="auto-advance-field">
                <input type="checkbox" x-model="autoAdvance">
                <span>Auto-advance to next exercise</span>
            </label>
        </div>
    </div>

    <div class="setting-option group">
        <div class="group__header">
            <h2 class="group__title">Click sound profile</h2>
            <p class="group__hint">
                Choose the sound used for the metronome click
            </p>
        </div>

        <div class="group__body">
            <select
                class="select-click-profile"
                x-model="activeDawProfileKey"
                x-effect="$el.value = activeDawProfileKey"
                @change="changeDawProfile($event.target.value)"
                :disabled="isPlaying"
            >
                <option value="ableton">Ableton</option>
                <option value="cubase">Cubase</option>
            </select>
        </div>
    </div>

    <div class="setting-option group">
        <div class="group__header">
            <h2 class="group__title">Sessions</h2>
            <p class="group__hint">
                Manage saved practice history on this device
            </p>
        </div>

        <div class="group__body">
            <button
                type="button"
                class="button"
                data-type="secondary"
                @click="clearAllRecentSessions()"
            >
                Clear session history
            </button>
        </div>
    </div>

    <div class="setting-option group">
        <div class="group__header">
            <h2 class="group__title">DANGER ZONE</h2>
            <p class="group__hint">
                These actions can't be undone
            </p>
        </div>

        <div class="group__body">
            <button
                type="button"
                class="button"
                data-type="danger-zone"
                @click="requestClearAllAppStorage()"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                Reset app
            </button>
        </div>

        <x-windows.reset-app-modal />
    </div>
</div>