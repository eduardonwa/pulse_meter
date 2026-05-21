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
            @change="changeDawProfile(activeDawProfileKey)"
            :disabled="isPlaying"
        >
            <template x-for="(profile, key) in dawProfiles" :key="key">
                <option
                    class="option-click-profile"
                    :value="key"
                    x-text="profile.label"
                ></option>
            </template>
        </select>
    </label>
</div>