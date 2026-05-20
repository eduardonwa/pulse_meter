<div class="modes" x-show="activeTab === 'sessions'" x-cloak>
    <div class="header">
        <h2>type</h2>
        
        <label for="mode">
            <select class="mode-selector" x-model="metronome.mode">
                <option value="timer">Timer</option>
                <option value="manual">Manual</option>
            </select>
        </label>
    </div>

    <article class="mode-timer" x-show="metronome.mode === 'timer'">
        <div class="time-display">
            <x-inputs.number-picker
                class="minutes"
                options="minutesOptions"
                model="metronomeMinutes"
                disabled="isPlaying"
            />

            <span class="colon">:</span>
            
            <x-inputs.number-picker
                class="seconds"
                options="secondsOptions"
                model="metronomeSeconds"
                disabled="isPlaying"
                format="(value) => String(value).padStart(2, '0')"
            />
        </div>
    </article>
</div>