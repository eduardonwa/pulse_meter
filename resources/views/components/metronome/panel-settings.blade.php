<div class="settings" x-show="activeTab === 'settings'" x-cloak>     
    <label class="setting-option timer-options " for="autoadvance">
        <p>Exercises</p>
        <div class="auto-advance-field">
            <input type="checkbox" x-model="autoAdvance">
            <span>Auto-advance to next exercise</span>
        </div>
    </label>

    <label class="setting-option click-profile" for="click-profile">
        <p>Click sound profile</p>

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
    </label>

    <label class="setting-option sessions" for="clear-session">
        <h2>Sessions</h2>
        <button
            type="button"
            class="button"
            data-type="secondary"
            @click="clearAllRecentSessions()"
        >
            Clear session history
        </button>
    </label>

    <label class="setting-option danger-zone" for="clear-app-storage">
        <h2 class="header">DANGER ZONE</h2>
        <button
            type="button"
            class="button"
            data-type="danger-zone"
            @click="clearAllAppStorage()"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            Reset app
        </button>
    </label>
</div>