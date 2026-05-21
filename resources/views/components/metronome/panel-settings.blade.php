<div class="settings" x-show="activeTab === 'settings'" x-cloak>     
    <label class="timer-options setting-option" for="autoadvance">
        <p>Timer options</p>
        <div class="auto-advance-field">
            <input type="checkbox" x-model="autoAdvance">
            <span>Auto-advance to next exercise</span>
        </div>
    </label>

    <label class="setting-option" for="click-profile">
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
</div>