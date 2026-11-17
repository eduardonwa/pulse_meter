<dialog class="dialog-shell dialog-shell--fit-content"
    x-ref="stepDialog"
    x-trap.noscroll="isStepFormOpen"
    @close="isStepFormOpen = false"
    @click.self="closeStepFormModal()"
    @cancel.prevent="closeStepFormModal()"
>
    <form class="new-exercise-form" @submit.prevent="saveStepForm()">
        <header class="heading">
            <h3 x-text="stepFormMode === 'edit' ? 'Edit exercise' : 'New exercise'"></h3>
            
            <button
                type="button"
                class="button"
                data-type="outline"
                @click="closeStepFormModal()"
                @cancel.prevent="closeStepFormModal()"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </button>            
        </header>

        <label class="exercise-name-wrapper">
            <span class="exercise-form-label">Name</span>
            <input type="text" class="name" x-model="stepForm.name">
        </label>

        <div class="exercise-settings" aria-label="Exercise settings">
            <div class="exercise-type-wrapper exercise-setting-row">
                <span class="exercise-form-label" id="exercise-type-label">Type</span>
                <div class="exercise-type-toggle" role="group" aria-labelledby="exercise-type-label">
                    <button type="button" class="exercise-type-toggle__option"
                        :class="{ 'is-active': stepForm.mode === 'classic' }"
                        :aria-pressed="stepForm.mode === 'classic'"
                        @click="stepForm.mode = 'classic'">
                        Classic
                    </button>
                    <button type="button" class="exercise-type-toggle__option"
                        :class="{ 'is-active': stepForm.mode === 'drill' }"
                        :aria-pressed="stepForm.mode === 'drill'"
                        @click="stepForm.mode = 'drill'">
                        Drill
                    </button>
                </div>
            </div>

            <div class="exercise-bpm-wrapper exercise-setting-row">
                <span class="exercise-form-label">BPM</span>

                <x-inputs.number-picker
                    class="exercise-row__bpm"
                    options="bpmOptions"
                    model="stepForm.bpm"
                    format="(value) => value"
                    :controls="true"
                    decrease-label="Decrease BPM"
                    increase-label="Increase BPM"
                    hint="Scroll to change BPM"
                />
            </div>

            <div class="exercise-length-wrapper exercise-setting-row"
                x-show="stepForm.mode === 'drill'" x-cloak>
                <span class="exercise-form-label">Length</span>

                <div class="length-inputs">
                    <div class="minutes">
                        <x-inputs.number-picker
                            options="stepFormMinutesOptions"
                            model="stepFormMinutes"
                            after-change="
                                normalizeStepFormDuration();
                                $dispatch('picker:sync');
                            "
                        />
                        <span class="unit">m</span>
                    </div>

                    <span class="colon">:</span>

                    <div class="seconds">
                        <x-inputs.number-picker
                            options="stepFormSecondsOptions"
                            model="stepFormSeconds"
                            format="(value) => String(value).padStart(2, '0')"
                            after-change="
                                normalizeStepFormDuration();
                                $dispatch('picker:sync');
                            "
                        />
                        <span class="unit">s</span>
                    </div>
                </div>
            </div>

            <label class="exercise-time-signature-wrapper exercise-setting-row"
                x-cloak x-show="stepFormMode === 'create' || !hasAlphaTabPattern(steps[stepFormIndex])">
                <span class="exercise-form-label">Time signature</span>

                <div style="display: flex; gap: .5rem; align-items: center;">
                    <select x-model.number="stepForm.time_signature_numerator" aria-label="Beats per measure">
                        @foreach (range(1, 16) as $numerator)
                            <option value="{{ $numerator }}">{{ $numerator }}</option>
                        @endforeach
                    </select>
                    <span>/</span>
                    <select x-model.number="stepForm.time_signature_denominator" aria-label="Beat note value">
                        <option value="2">2</option><option value="4">4</option><option value="8">8</option><option value="16">16</option>
                    </select>
                </div>
            </label>
        </div>

        <div class="form-actions">
            @can('use-pro')
            <button x-show="stepFormMode === 'create' && canCreateTablatures" type="button" class="button" data-type="outline"
                @click="openNewTabEditor()">
                Create with tablature
            </button>
            @endcan
            <button class="button" data-type="primary" type="submit"
                x-text="stepFormMode === 'edit' ? 'Save changes' : 'Save'"
            ></button>

            <button class="button" data-type="outline" type="button"
                @click="closeStepFormModal()"
                @cancel.prevent="closeStepFormModal()"
            >
                Cancel
            </button>
        </div>
    </form>
</dialog>

{{-- Separate native dialog: the exercise does not exist until Save is confirmed. --}}

@can('use-pro')
    <dialog class="dialog-shell dialog-shell--bottom-sheet mobile-exercise-view-dialog is-fullscreen" x-ref="tabEditorDialog"
        @alphatab:playback-state.window="if ($event.detail?.element === $refs.tabEditorPreview) tabPreviewPlaying = Boolean($event.detail.playing)"
        @keydown="handleTabEditorKey($event)"
        @cancel.prevent="closeNewTabEditor()"
        @close="tabEditorOpen = false"
    >
        <header class="mobile-exercise-view-dialog__header">
            <strong x-text="tabEditorEditingIndex === null ? 'New tablature' : 'Edit tablature'"></strong>
            
            <button class="button" type="button" data-type="icon" aria-label="Close tablature editor"
                @click="closeNewTabEditor()">
                <x-heroicon-o-x-mark />
            </button>
        </header>

        <div class="mobile-exercise-view-dialog__content tab-editor">
            <p class="tab-editor__intro">Add notes to your exercise. The score will be saved when you finish.</p>
            <div class="tab-editor__import" x-show="tabEditorEditingIndex === null" x-cloak>
                <label class="button" data-type="outline">
                    Import MusicXML
                    <input type="file" accept=".xml,.musicxml,application/xml,text/xml"
                        @change="importTabMusicXml($event)" style="display:none">
                </label>
                <small>From Guitar Pro, MuseScore or another notation editor. Notes, strings, frets and rhythm only.</small>
                <div x-show="tabMusicXmlImport" x-cloak>
                    <p x-text="`${tabMusicXmlImport?.sections.length ?? 0} compatible section(s) found. Select one to import; changes of meter are separate exercises.`"></p>
                    <label>
                        Section
                        <select x-model.number="tabMusicXmlSelectedSection">
                            <template x-for="(section, index) in (tabMusicXmlImport?.sections ?? [])" :key="index">
                                <option :value="index" x-text="`Measures ${section.firstMeasure}–${section.lastMeasure} · ${section.numerator}/${section.denominator}`"></option>
                            </template>
                        </select>
                    </label>
                    <div x-show="tabMusicXmlImport?.warnings.length" x-cloak>
                        <p>Some measures cannot be imported with the current editor:</p>
                        <ul>
                            <template x-for="(warning, index) in (tabMusicXmlImport?.warnings ?? [])" :key="index">
                                <li x-text="warning"></li>
                            </template>
                        </ul>
                    </div>
                    <button type="button" class="button" data-type="primary" @click="applyTabMusicXmlSection()">
                        Use selected section
                    </button>
                </div>
            </div>
            <div class="tab-editor__settings">
                {{-- SETTINGS --}}
                <label>
                    Exercise name
                    <input
                        type="text"
                        x-model="stepForm.name"
                        maxlength="255">
                </label>
                
                <label>
                    BPM
                    <input
                        type="number"
                        x-model.number="stepForm.bpm"
                        min="30"
                        max="400">
                </label>
                
                <label>
                    Time signature
                    <span style="display: flex; gap: .5rem; align-items: center;">
                        <select x-model.number="stepForm.time_signature_numerator" aria-label="Beats per measure">
                            @foreach (range(1, 16) as $numerator)
                                <option value="{{ $numerator }}">{{ $numerator }}</option>
                            @endforeach
                        </select>
                        <span>/</span>
                        <select x-model.number="stepForm.time_signature_denominator" aria-label="Beat note value">
                            <option value="2">2</option><option value="4">4</option><option value="8">8</option><option value="16">16</option>
                        </select>
                    </span>
                </label>
                <label>
                    Instrument
                    <select x-model.number="tabDraft.instrument">
                        <option value="24">Nylon guitar</option>
                        <option value="25">Steel guitar</option>
                        <option value="26">Jazz guitar</option>
                        <option value="27">Clean guitar</option>
                    </select>
                </label>

                <label>
                    String
                    <select x-model.number="tabDraft.string">
                        <template x-for="(name, i) in tabStringNames" :key="i">
                            <option :value="i + 1" x-text="`${i + 1} · ${name}`"></option>
                        </template>
                    </select>
                </label>

                <label>
                    Fret
                    <input
                        type="number"
                        x-model.number="tabDraft.fret"
                        min="0"
                        max="24">
                </label>

                <label>
                    Duration
                    <select x-model.number="tabDraft.duration" @change="changeTabEditorDuration($event.target.value)">
                        <option value="4">Quarter</option>
                        <option value="8">Eighth</option>
                        <option value="16">Sixteenth</option>
                        <option value="2">Half</option>
                        <option value="1">Whole</option>
                    </select>
                </label>
            </div>

            <div class="tab-editor__actions">
                <button type="button" class="button" data-type="primary" @click="addTabEditorNote()">Add note</button>
                <button type="button" class="button" data-type="outline" :disabled="!tabDraft.beats.length" @click="previewTabEditor()" x-text="tabPreviewPlaying ? 'Stop' : 'Play'"></button>
                <button type="button" class="button" data-type="outline" :disabled="!tabDraft.beats.length"
                    @click="undoTabEditorNote()">Undo last note</button>
            </div>

            <div class="tab-editor__preview" x-show="tabPreviewPlaying" x-cloak><div x-ref="tabEditorPreview" data-alphatab></div></div>
                <p x-text="`${tabDraft.beats.length} notes · ${stepForm.time_signature_numerator}/${stepForm.time_signature_denominator} · arrows navigate, 0-9 enter frets`"></p>
                
                <p class="tab-editor__selection-hint">
                    Drag to select a range · Shift + ←/→ extends selection · Shift + click selects a range · Ctrl/Cmd + click toggles notes · type 10–24 quickly for two-digit frets · Backspace clears to rest · Delete removes position.
                </p>
                
                <p class="tab-editor__measure-status" x-text="tabEditorMeasureStatus()"></p>
                
                <div class="tab-editor__measure-list" aria-live="polite">
                    <template x-for="measure in tabEditorMeasureWarnings()" :key="measure.number">
                        <span class="tab-editor__measure-indicator" :data-status="measure.status" :title="measure.message" x-text="measure.message"></span>
                    </template>
                </div>
                
                <div class="tab-editor__score" x-ref="tabEditorScore" @pointerup.window="endTabDrag()" @pointercancel.window="endTabDrag()" @resize.window="updateTabEditorWidth()" tabindex="0" role="group" aria-label="Interactive six-string tablature">
                    <template x-for="(system, systemIndex) in tabEditorSystems()" :key="systemIndex">
                        <div class="tab-editor__system">
                            <div class="tab-editor__string-labels">
                                <template x-for="(name, stringIndex) in tabStringNames" :key="stringIndex">
                                    <span x-text="name"></span>
                                </template>
                            </div>

                            <div class="tab-editor__grid">
                                <template x-for="(name, stringIndex) in tabStringNames" :key="stringIndex">
                                    <div class="tab-editor__string-row">
                                        <template x-for="column in system" :key="`${column.index}-${column.insertion}`">
                                            <button type="button" class="tab-editor__cell"
                                                :class="{ 'is-selected': tabSelectedIndex === column.index && tabDraft.string === stringIndex + 1 && tabInsertAtBar === column.insertion, 'is-measure-end': column.measureEnd, 'is-multi-selected': !column.insertion && tabSelectedIndices.includes(column.index) && column.index < tabDraft.beats.length, 'is-insertion': column.insertion }"
                                                :aria-label="column.insertion ? `Insert note before next measure, string ${stringIndex + 1}` : `String ${stringIndex + 1}, position ${column.index + 1}`"
                                                :aria-pressed="tabSelectedIndex === column.index && tabDraft.string === stringIndex + 1 && tabInsertAtBar === column.insertion"
                                                @pointerdown="!column.insertion && startTabDrag(column.index, stringIndex + 1, $event)"
                                                @pointerenter="!column.insertion && extendTabDrag(column.index)"
                                                @click="column.insertion ? selectTabEditorInsertion(column.index, stringIndex + 1) : clickTabCell(column.index, stringIndex + 1, $event); $refs.tabEditorScoreFocus?.focus()"
                                                x-text="column.insertion ? (stringIndex + 1 === tabDraft.string ? '+' : '·') : (tabEditorNoteAt(column.index, stringIndex + 1) ?? '·')">
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                
                <span x-ref="tabEditorScoreFocus" class="sr-only" tabindex="-1">Tab keyboard controls</span>
                
                <div class="tab-editor__actions">
                    <button type="button" class="button" data-type="primary"
                        :disabled="tabEditorSaving || !tabDraft.beats.length" @click="saveNewTabExercise(false)"
                        x-text="tabEditorSaving ? 'Saving…' : 'Save'"></button>
                    <button type="button" class="button" data-type="outline"
                        :disabled="tabEditorSaving || !tabDraft.beats.length" @click="saveNewTabExercise(true)">
                        Save &amp; Close
                    </button>
                    <button type="button" class="button" data-type="outline" :disabled="tabEditorSaving"
                        @click="closeNewTabEditor()">Cancel</button>
                </div>
            </div>
    </dialog>
@endcan
