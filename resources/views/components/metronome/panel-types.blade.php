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
            <template x-if="!isPlaying">
                <div class="time-picker">
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
            </template>

            <template x-if="isPlaying">
                <div class="timer-readout">
                    <span x-text="Math.floor((remaining ?? metronome.duration_seconds) / 60)"></span>
                    <span class="colon">:</span>
                    <span x-text="String((remaining ?? metronome.duration_seconds) % 60).padStart(2, '0')"></span>
                </div>
            </template>
        </div>
    </article>
</div>