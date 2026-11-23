<div class="pulse-editor">
    <x-metronome.creative-transport variant="mobile" />

    <div class="time-signature">
        <x-metronome.pulse-beats
            groups="getPatternGroups()"
            beat-action="applyEditorTool(item.beat)"
            subdivision-action="applyEditorToolToSubdivision(item.beat, subdivisionIndex)"
        />

        <div class="time-signature__rhythm-tools">
            <h2 class="heading">Editing tools</h2>

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

                <button class="button" type="button" data-tether-trigger
                    @click="
                        editorTool = 'groupStart';
                        startToolTether($event);
                    "
                    :class="{ 'is-selected': editorTool === 'groupStart' }"
                >
                    Group start
                </button>
            </div>
        </div>

        <div class="time-signature__subdivision">
            <h2 class="heading">Subdivision</h2>

            <label>
                <select class="subdivision-selector" x-model.number="subdivision" @change="setSubdivision($event.target.value)">
                    <template x-for="option in subdivisionOptions" :key="option.value">
                        <option :value="option.value" x-text="option.label"></option>
                    </template>
                </select>
            </label>
        </div>
        

        <div class="time-signature__playback">
            <h2 class="heading">Playback</h2>

            <div class="tool-group">
                <button
                    class="button"
                    type="button"
                    @click="creativePlaybackMode = 'click'"
                    :class="{ 'is-selected': creativePlaybackMode === 'click' }"
                >
                    Click
                </button>

                <button
                    class="button"
                    type="button"
                    @click="creativePlaybackMode = 'pulse'"
                    :class="{ 'is-selected': creativePlaybackMode === 'pulse' }"
                >
                    Pulse
                </button>
            </div>
        </div>

        <div class="pulse-settings-bar">
            <button class="button" data-type="outline" type="button"
                @click="$refs.pulseSettingsSheet.showModal()"
                aria-haspopup="dialog"
            >
                Pattern Settings
            </button>
        </div>

        <x-metronome.creative-transport variant="desktop" />

        <div class="time-signature__grouping">
            <h2 class="heading">Grouping</h2>
            <div class="current" x-text="getGroupingLabel()"></div>
        </div>
        
        <div class="time-signature__actions">
            <button class="button" data-type="primary" type="button" @click="pulseDraft.origin === 'new' ? openPulsePatternNameDialog('new') : savePulsePattern()" x-show="pulseDraft.origin === 'new' || pulseDraft.origin === 'user'">
                Save
            </button>

            <button class="button" data-type="primary" data-variant="outline" type="button" @click="openPulsePatternNameDialog('copy')" x-show="pulseDraft.origin === 'preset' || pulseDraft.origin === 'user'">
                Save as copy
            </button>
        </div>
    </div>

    <dialog class="dialog-shell dialog-shell--bottom-sheet pulse-settings-sheet"
        x-ref="pulseSettingsSheet"
        aria-labelledby="pulse-settings-title"
        @click.self="$refs.pulseSettingsSheet.close()"
    >
        <div class="dialog-shell__content pulse-settings-sheet__content">
            <header class="pulse-settings-sheet__header">
                <span class="pulse-settings-sheet__handle" aria-hidden="true"></span>
                <h2 id="pulse-settings-title" class="heading">Pattern Settings</h2>
                <button class="button" type="button" data-type="icon"
                    aria-label="Close pattern settings"
                    @click="$refs.pulseSettingsSheet.close()"
                >
                    <x-heroicon-o-x-mark />
                </button>
            </header>

            <div class="pulse-settings-sheet__section">
                <h3 class="heading">Patterns</h3>
                <button class="button" data-type="outline" type="button"
                    @click="$refs.pulseSettingsSheet.close(); $nextTick(() => openPatternDialog())"
                >
                    <span x-text="getCurrentPulseSourceLabel()"></span>
                </button>
                <button class="button" data-type="outline" type="button"
                    @click="$refs.pulseSettingsSheet.close(); resetPulse()"
                    title="Reset to a new 4/4 Pulse pattern"
                >
                    Reset Pulse
                </button>
            </div>

            <div class="pulse-settings-sheet__section">
                <div class="time-signature__meter pulse-settings-sheet__meter">
                    <h3 class="heading">
                        Meter
                    </h3>

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

            </div>
        </div>
    </dialog>
</div>
