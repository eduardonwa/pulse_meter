<fieldset class="recent-sessions" x-show="metronome.mode !== 'creative' && (recentSessions[metronome.mode]?.length ?? 0) > 0" x-cloak>
    <legend class="subheading" data-type="mini">Recent</legend>
    
    <div class="recent-sessions__list">
        <template x-for="session in (recentSessions[metronome.mode] ?? [])" :key="session.id">
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