<div class="modes" x-show="activeTab === 'sessions'" x-cloak>
    <div class="header">
        <h2>type</h2>
        
        <label for="mode">
            <select class="mode-selector" x-model="metronome.mode"
                @change="
                    trackSessionTypeSelected($event.target.value);

                    if ($event.target.value === 'timer') {
                        $nextTick(() => {
                            requestAnimationFrame(() => {
                                window.dispatchEvent(
                                    new Event('picker:sync')
                                )
                            })
                        })
                    }
                "
            >
                <option value="timer">Timer</option>
                <option value="classic">Classic</option>
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

    <fieldset class="recent-sessions" x-show="recentSessions[metronome.mode]?.length">
        <legend class="subheading" data-type="mini">Recent</legend>
        
        <div class="recent-sessions__list">
            <template x-for="session in recentSessions[metronome.mode]" :key="session.id">
                <button
                    type="button"
                    class="button"
                    data-type="outline"
                    @click="loadSession(session)"
                >
                    <span x-text="`${session.bpm} BPM`"></span>

                    <template x-if="session.type === 'timer'">
                        <span class="duration-label"
                            x-text="`${Math.floor(session.duration_seconds / 60)}:${String(session.duration_seconds % 60).padStart(2, '0')}`"
                        ></span>
                    </template>
                </button>
            </template>
        </div>

        <div class="footer">
            <span class="link" @click="clearRecentSessionsForCurrentMode()">
                Clear
            </span>
        </div>
    </fieldset>
</div>