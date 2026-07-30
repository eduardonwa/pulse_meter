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
            <!-- RHYTHM EDITOR -->
            <div class="pulse-editor">
                <div class="time-signature">
                    <div class="time-signature__pattern">
                        <h2 class="heading">
                            Patterns
                        </h2>

                        <button class="button" data-type="outline" type="button" @click="openPatternDialog()">
                            <span x-text="getCurrentPulseSourceLabel()"></span>
                        </button>

                        {{-- esta en welcome --}}
                    </div>

                    <div class="time-signature__meter">
                        <h2 class="heading">
                            Meter
                        </h2>

                        <div class="meter-control">
                            <x-inputs.number-picker
                                class="meter-numerator"
                                options="meterNumeratorOptions"
                                model="timeSignature.numerator"
                                afterChange="setDraftNumerator(value)"
                                :controls="true"
                                decrease-label="Decrease numerator"
                                increase-label="Increase numerator"
                                hint="Scroll to change numerator"
                                :controls-on-mobile="false"
                            />

                            <span class="meter-control__separator">/</span>

                            <x-inputs.number-picker
                                class="meter-denominator"
                                options="meterDenominatorOptions"
                                model="timeSignature.denominator"
                                afterChange="setDraftDenominator(value)"
                                :controls="true"
                                decrease-label="Decrease beat unit"
                                increase-label="Increase beat unit"
                                hint="Scroll to change beat unit"
                                :controls-on-mobile="false"
                            />
                        </div>
                    </div>
        
                    <div class="time-signature__grouping">
                        <h2 class="heading">Grouping</h2>
                        <div class="current" x-text="getGroupingFromPattern().join(' + ')"></div>
                    </div>
                    
                    <div class="time-signature__beats">
                        <h2 class="heading">Beats</h2>

                        <div class="beat-groups">
                            <template x-for="(group, groupIndex) in getPatternGroups()" :key="groupIndex">
                                <div class="beat-group badge"
                                    :class="[
                                        'badge--group-beat-a',
                                        'badge--group-beat-b',
                                        'badge--group-beat-c',
                                        'badge--group-beat-d'
                                    ][groupIndex % 4]"
                                >
                                    <template x-for="item in group" :key="item.beat">
                                        <button class="beat-mark" type="button" @click="applyEditorTool(item.beat)"
                                            :class="{
                                                'is-group-start': item.groupStart,
                                                'is-active': currentBeat === item.beat,
                                                'is-accent': item.sound === 'accent',
                                                'is-click': item.sound === 'click',
                                                'is-rest': item.sound === 'rest'
                                            }"
                                        >
                                            <span x-text="
                                                item.sound === 'accent'
                                                    ? 'A'
                                                    : item.sound === 'click'
                                                        ? 'C'
                                                        : item.sound === 'rest'
                                                            ? 'R'
                                                            : '-'
                                                "
                                            ></span>

                                            <small x-text="item.beat"></small>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <div class="time-signature__rhythm-tools">
                        <h2 class="heading">Cue</h2>
        
                        <div class="tool-group">
                            <button class="button" type="button" data-tether-trigger
                                @click="
                                    editorTool = 'accent';
                                    startToolTether($event);
                                "
                                :class="{ 'is-selected': editorTool === 'accent' }"
                            >
                                Accent
                            </button>

                            <button class="button" type="button" data-tether-trigger
                                @click="
                                    editorTool = 'click';
                                    startToolTether($event);
                                "
                                :class="{ 'is-selected': editorTool === 'click' }"
                            >
                                Click
                            </button>

                            <button class="button" type="button" data-tether-trigger
                                @click="
                                    editorTool = 'rest';
                                    startToolTether($event);
                                "
                                :class="{ 'is-selected': editorTool === 'rest' }"
                            >
                                Rest
                            </button>
                        </div>
                    </div>
        
                    <div class="time-signature__rhythm-structure">
                        <h2 class="heading">Structure</h2>

                        <button class="button" type="button" data-tether-trigger
                            @click="
                                editorTool = 'groupStart';
                                startToolTether($event);
                            "
                            :class="{ 'is-selected': editorTool === 'groupStart' }"
                        >
                            Group Start
                        </button>
                    </div>

                    <div class="time-signature__actions">
                        <button class="button" data-type="secondary" type="button" @click="savePulsePattern()" x-show="pulseDraft.origin === 'new' || pulseDraft.origin === 'user'">
                            Save
                        </button>
        
                        <button class="button" data-type="secondary" type="button" @click="savePulsePatternAs()" x-show="pulseDraft.origin === 'preset' || pulseDraft.origin === 'user'">
                            Save As
                        </button>
                    </divv>
                </div>
            </div>
        </section>
    @endcan
</div>