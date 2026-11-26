<div class="pulse-editor">
    <x-metronome.creative-transport variant="mobile" />

    <div class="time-signature">
        <div class="time-signature__content">
            <x-metronome.pulse-beats
                groups="getPatternGroups()"
                beat-action="applyEditorTool(item.beat)"
                subdivision-action="applyEditorToolToSubdivision(item.beat, subdivisionIndex)"
            />

            <div class="time-signature__subdivision">
                <h2 class="heading">Subdivision</h2>
    
                <div class="subdivision-options" role="group" aria-label="Subdivision">
                    <template x-for="option in subdivisionOptions" :key="option.value">
                        <button class="button" type="button"
                            @click="setSubdivision(option.value)"
                            :class="{ 'is-selected': subdivision === option.value }"
                            :aria-pressed="subdivision === option.value"
                            x-text="option.value === 1 ? 'Off' : option.label"
                        ></button>
                    </template>
                </div>
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

            <div class="time-signature__rhythm-tools">
                <h2 class="heading">Editing</h2>
    
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

            <x-metronome.creative-transport variant="desktop" />
        </div>

        <div class="time-signature__management">
            <div class="time-signature__actions">
                <button class="button" data-type="primary" type="button" @click="pulseDraft.origin === 'new' ? openPulsePatternNameDialog('new') : savePulsePattern()" x-show="pulseDraft.origin === 'new' || pulseDraft.origin === 'user'" title="Save" aria-label="Save">
                    <x-lucide-save />
                    <span>Save</span>
                </button>

                <button class="button" data-type="primary" data-variant="outline" type="button" @click="openPulsePatternNameDialog('copy')" x-show="pulseDraft.origin === 'preset' || pulseDraft.origin === 'user'" title="Save as copy" aria-label="Save as copy">
                    <x-lucide-copy />
                    <span>Save as copy</span>
                </button>
            </div>

            <div class="pulse-settings-bar">
                <button class="button" data-type="outline" type="button" @click="$refs.pulseSettingsSheet.showModal()" aria-haspopup="dialog" title="Pattern settings" aria-label="Pattern settings">
                    <x-lucide-sliders-vertical />
                    <span>Pattern Settings</span>
                </button>
            </div>
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
                
                <h2 id="pulse-settings-title" class="heading">
                    Pattern Settings
                </h2>
                
                <button class="button" type="button" data-type="icon"
                    aria-label="Close pattern settings"
                    @click="$refs.pulseSettingsSheet.close()"
                >
                    <x-heroicon-o-x-mark />
                </button>
            </header>

            <div class="pulse-settings-sheet__section pulse-settings-sheet__section--actions">
                <div class="pulse-settings-sheet__pattern-controls">
                    <button class="button" data-type="outline" type="button"
                        @click="$refs.pulseSettingsSheet.close(); $nextTick(() => openPatternDialog())"
                        aria-label="Browse saved and preset patterns"
                    >
                        <x-lucide-library />
                        Browse Patterns
                    </button>

                    <button class="button" data-type="accent" type="button"
                        @click="$refs.pulseSettingsSheet.close(); resetPulse()"
                        aria-label="Create a new Common Time (4/4) pattern"
                    >
                        <x-lucide-plus />
                        New
                    </button>

                    <button class="button" data-type="outline" type="button"
                        @click="$refs.pulseSettingsSheet.close(); resetPulse()"
                        title="Reset to a new 4/4 Pulse pattern"
                    >
                        <x-lucide-rotate-ccw />
                        Reset
                    </button>
                </div>
            </div>

            <div class="pulse-settings-sheet__section">
                <h3 class="heading">Meter</h3>
                
                <div class="pulse-settings-sheet__value pulse-settings-sheet__meter">
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

            <div class="pulse-settings-sheet__section">
                <h3 class="heading">Grouping</h3>
                <div class="pulse-settings-sheet__value">
                    <span class="current" x-text="getGroupingLabel()"></span>
                </div>
            </div>
        </div>
    </dialog>
</div>
