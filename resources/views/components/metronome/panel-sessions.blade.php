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
                <option value="classic">Classic</option>
                @can('use-pro')
                    <option value="creative">Creative</option>
                @endcan
                <option value="timer">Timer</option>
            </select>
        </label>
    </div>

    {{-- TIMER --}}
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

    {{-- CLASSIC --}}
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

    <!-- CREATIVE -->
    @can('use-pro')
        <section class="mode-creative" x-show="metronome.mode === 'creative'" x-cloak>
            <div class="time-signature-control">
                <label for="time-signature">
                    Time Signature
                </label>

                <select id="time-signature"
                    @change="
                        const signature = timeSignatures[$event.target.selectedIndex];
                        setTimeSignature(signature);
                    "
                >
                    <template
                        x-for="signature in timeSignatures"
                        :key="`${signature.numerator}/${signature.denominator}`"
                    >
                        <option :value="`${signature.numerator}/${signature.denominator}`"
                            x-text="`${signature.numerator}/${signature.denominator}`"
                            :selected="
                                timeSignature.numerator === signature.numerator &&
                                timeSignature.denominator === signature.denominator
                            "
                        ></option>
                    </template>
                </select>
            </div>

            <!-- RHYTHM EDITOR -->
            <article class="time-signature">
                <div class="time-signature__beats">
                    <template x-for="beat in timeSignature.numerator" :key="beat">
                        <button class="beat-mark" type="button"
                            @click="applyEditorTool(beat)"
                            :class="{
                                'group-a': getGroupIndexForBeat(beat) % 2 === 0,
                                'group-b': getGroupIndexForBeat(beat) % 2 !== 0,

                                'is-group-start': pattern[beat - 1]?.groupStart,
                                'is-active': currentBeat === beat,
                                'is-accent': pattern[beat - 1]?.sound === 'accent',
                                'is-click': pattern[beat - 1]?.sound === 'click',
                                'is-rest': pattern[beat - 1]?.sound === 'rest'
                            }"
                        >
                            <span
                                x-text="
                                    pattern[beat - 1]?.sound === 'accent'
                                        ? 'A'
                                        : pattern[beat - 1]?.sound === 'click'
                                            ? 'C'
                                            : pattern[beat - 1]?.sound === 'rest'
                                                ? 'R'
                                                : '-'
                                "
                            ></span>

                            <small x-text="beat"></small>
                        </button>
                    </template>
                </div>

                <div class="time-signature__grouping" x-text="getGroupingFromPattern().join(' + ')"></div>

                <div class="rhythm-tools">
                    <button type="button" @click="editorTool = 'accent'"
                        :class="{ 'is-selected': editorTool === 'accent' }"
                    >
                        Accent
                    </button>

                    <button type="button" @click="editorTool = 'click'"
                        :class="{ 'is-selected': editorTool === 'click' }"
                    >
                        Click
                    </button>

                    <button type="button" @click="editorTool = 'rest'"
                        :class="{ 'is-selected': editorTool === 'rest' }"
                    >
                        Rest
                    </button>

                    <button type="button" @click="editorTool = 'groupStart'"
                        :class="{ 'is-selected': editorTool === 'groupStart' }"
                    >
                        Group Start
                    </button>
                </div>
            </article>
        </section>
    @endcan
</div>