{{-- Tablature editor for routine exercises. --}}
<dialog class="tab-editor-dialog" x-ref="tabEditorDialog"
    @cancel.prevent="closeNewTabEditor()" @close="tabEditorOpen = false"
    @alphatab:playback-state.window="syncPrototypePlayback($event)"
    @alphatab:playback-position.window="syncPrototypePlaybackPosition($event)"
    @alphatab:played-beat.window="syncPrototypePlayedBeat($event)"
    @alphatab:score-started.window="startPrototypeScorePlayhead($event)"
    @alphatab:playback-finished.window="finishPrototypePlayback($event)"
    @alphatab:load-error.window="prototypePlaybackError($event)"
    @alphatab:editor-select.window="selectTabEditorScoreBeat($event)"
    @alphatab:rendered.window="if ($event.detail?.element === $refs.tabEditorPreview) syncTabEditorScoreCaret()"
>
    {{-- Display toasts inside the modal's top layer while it is open. --}}
    <div class="toaster" data-align="bottom" data-pos="right" aria-live="polite" aria-atomic="true">
        <div class="toast" x-show="toast.visible" x-cloak x-transition>
            <div class="toast__body" :class="`toast__body--${toast.type}`">
                <span x-text="toast.message"></span>
                <button class="button" data-type="icon" type="button" aria-label="Close notification"
                    @click="hideToast()"><x-heroicon-o-x-mark /></button>
            </div>
        </div>
    </div>

    <div class="tab-editor">
        <div class="tab-editor__editor">
            {{-- Exercise name and save actions --}}
            <header class="tab-editor__header">
                <div class="tab-editor__heading">
                    <h2 class="exercise-name" x-text="stepForm.name || 'Untitled exercise'">Nombre del ejercicio</h2>
                    <p class="tab-editor__save-status" :class="`is-${prototypeMessageType}`" role="status" aria-live="polite" x-text="prototypeMessage"></p>
                </div>

                <div class="tab-editor__actions">
                    <button class="button save" data-type="icon-text" type="button" @click="saveNewTabExercise(false)" :disabled="tabEditorSaving">
                        <x-lucide-save /> Save
                    </button>
                    <button class="button save-close" data-type="icon-text" type="button" @click="saveNewTabExercise(true)" :disabled="tabEditorSaving">
                        <x-lucide-save-check /> Save and close
                    </button>
                    <button class="button" data-type="outline" type="button" @click="closeNewTabEditor()">
                        Cancel
                    </button>
                </div>
            </header>

            {{-- import --}}
            <section class="tab-editor__import-bar" aria-label="Import options">
                <label class="button" data-type="secondary" data-variant="outline">
                    Import MusicXML
                    <input type="file" accept=".xml,.musicxml,application/xml,text/xml"
                        @change="importPrototypeMusicXml($event)" class="sr-only">
                </label>

                <div class="tab-editor__import-panel" x-show="prototypeMusicXmlFullSource" x-cloak role="region" aria-label="MusicXML import result">
                    <div class="tab-editor__import-result">
                        <strong class="verdict" x-text="canImportPrototypeMusicXmlForEditing() ? 'Ready to edit' : 'Playback only'"></strong>
                        <p class="reasoning" x-text="canImportPrototypeMusicXmlForEditing()
                            ? 'This tablature is compatible with the Dorelog editor. Import it to edit the complete tablature.'
                            : 'This score contains notation the Dorelog editor cannot represent as one editable exercise. You can still import the complete score for playback.'"></p>
                    </div>

                    <details class="tab-editor__import-warnings" x-ref="tabEditorImportDetails" x-show="!canImportPrototypeMusicXmlForEditing()" x-cloak>
                        <summary>Why is editing unavailable?</summary>
                        
                        <div class="tab-editor__import-details">
                            <p x-show="prototypeMusicXmlImport?.sections?.length > 1"
                                x-text="prototypeMusicXmlHasMultipleTimeSignatures()
                                    ? 'This score contains separate sections, including changes of time signature. The editor currently supports one time signature per exercise.'
                                    : 'This score contains separate sections because some measures cannot be edited.'"></p>

                            <div x-show="(prototypeMusicXmlImport?.sections?.length ?? 0) > 1">
                                <strong>Compatible sections</strong>
                                <ul>
                                    <template x-for="(section, index) in (prototypeMusicXmlImport?.sections ?? [])" :key="index">
                                        <li x-text="`${section.firstMeasure === section.lastMeasure ? 'Measure' : 'Measures'} ${section.firstMeasure}${section.firstMeasure === section.lastMeasure ? '' : '–' + section.lastMeasure}: ${section.numerator}/${section.denominator}`"></li>
                                    </template>
                                </ul>
                            </div>

                            <p x-show="prototypeMusicXmlIssue" x-text="prototypeMusicXmlIssue"></p>
                            
                            <div x-show="prototypeMusicXmlImport?.warnings?.length">
                                <strong>Unsupported measures</strong>
                                <ul>
                                    <template x-for="(warning, index) in (prototypeMusicXmlImport?.warnings ?? [])" :key="index">
                                        <li x-text="warning"></li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </details>

                    <div class="tab-editor__import-actions">
                        <button class="button" data-type="primary" type="button" x-show="canImportPrototypeMusicXmlForEditing()" x-cloak @click="applyPrototypeMusicXmlSection()">
                            Import for editing
                        </button>
                        
                        <button class="button" data-type="primary" type="button" x-show="!canImportPrototypeMusicXmlForEditing()" x-cloak @click="applyPrototypeMusicXmlFullScore()">
                            Import for playback
                        </button>
                        <button class="button" data-type="outline" type="button" @click="dismissPrototypeMusicXml()">
                            Dismiss
                        </button>
                    </div>
                </div>
            </section>

            {{-- Quick settings and additional exercise information --}}
            <section class="tab-editor__utility-bar" aria-label="Exercise information"
                x-data="{ infoOpen: false, shortcutsOpen: false }"
            >
                <div class="tab-editor__utility-row">
                    <div class="tab-editor__quick-settings">
                        <label class="tab-editor__field">
                            <span>BPM</span>
                            <input type="number" min="30" max="400" x-model.number="stepForm.bpm">
                        </label>

                        <div class="tab-editor__field">
                            <span>Time signature</span>
                            <div class="tab-editor__time-signature">
                                <select x-model.number="stepForm.time_signature_numerator" aria-label="Beats per measure">
                                    @foreach (range(1, 16) as $numerator)
                                        <option value="{{ $numerator }}">{{ $numerator }}</option>
                                    @endforeach
                                </select>
                                <span aria-hidden="true">/</span>
                                <select x-model.number="stepForm.time_signature_denominator" aria-label="Beat note value">
                                    <option value="2">2</option>
                                    <option value="4">4</option>
                                    <option value="8">8</option>
                                    <option value="16">16</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="tab-editor__view-switch" x-show="!tabFullMusicXml" x-cloak role="group" aria-label="Tablature view">
                        <button type="button" :class="{ 'is-active': tabScoreView === 'grid' }"
                            :aria-pressed="(tabScoreView === 'grid').toString()" @click="setTabScoreView('grid')">Grid</button>
                        <button type="button" :class="{ 'is-active': tabScoreView === 'score' }"
                            :aria-pressed="(tabScoreView === 'score').toString()" @click="setTabScoreView('score')">Score</button>
                    </div>
                    <div class="tab-editor__utility-actions">
                        <button class="button" data-type="outline" type="button"
                            aria-controls="tab-editor-info-panel"
                            :aria-expanded="infoOpen.toString()"
                            @click="infoOpen = !infoOpen; shortcutsOpen = false"
                        >
                            <x-lucide-info /> Info <x-lucide-chevron-down width="16" height="16" />
                        </button>

                        <div class="tab-editor__shortcuts">
                            <button class="button" data-type="outline" type="button"
                                aria-controls="tab-editor-shortcuts-panel"
                                :aria-expanded="shortcutsOpen.toString()"
                                @click="shortcutsOpen = !shortcutsOpen"
                                @keydown.escape="shortcutsOpen = false"
                            >
                                <x-lucide-keyboard width="18" height="18" /> Shortcuts
                            </button>

                            <div id="tab-editor-shortcuts-panel" class="tab-editor__shortcuts-popover"
                                x-show="shortcutsOpen" x-cloak @click.outside="shortcutsOpen = false"
                                @keydown.escape.stop="shortcutsOpen = false"
                            >
                                <strong>Keyboard shortcuts</strong>
                                <dl>
                                    <div><dt>Enter fret</dt><dd>0-9</dd></div>
                                    <div><dt>Navigate notes</dt><dd>← / →</dd></div>
                                    <div><dt>Change string</dt><dd>↑ / ↓</dd></div>
                                    <div><dt>Change duration</dt><dd>+ / -</dd></div>
                                    <div><dt>Hammer-on</dt><dd>H</dd></div>
                                    <div><dt>Pull-off</dt><dd>P</dd></div>
                                    <div><dt>Palm mute</dt><dd>M</dd></div>
                                    <div><dt>Tie</dt><dd>L</dd></div>
                                    <div><dt>Rest</dt><dd>R</dd></div>
                                    <div><dt>Extend selection</dt><dd>Shift + ← / →</dd></div>
                                    <div><dt>Undo</dt><dd>Ctrl/Cmd + Z</dd></div>
                                    <div><dt>Redo</dt><dd>Ctrl/Cmd + Shift + Z</dd></div>
                                    <div><dt>Clear note</dt><dd>Backspace</dd></div>
                                    <div><dt>Delete position</dt><dd>Delete</dd></div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-editor-info-panel" class="tab-editor__info-panel"
                    x-show="infoOpen" x-cloak
                >
                    <label class="tab-editor__field">
                        <span>Exercise name</span>
                        <input type="text" x-model="stepForm.name" maxlength="255">
                    </label>

                    <label class="tab-editor__field">
                        <span>Instrument</span>
                        <select x-model.number="tabDraft.instrument">
                            <option value="24">Nylon guitar</option>
                            <option value="25">Steel guitar</option>
                            <option value="26">Jazz guitar</option>
                            <option value="27">Clean guitar</option>
                        </select>
                    </label>
                </div>
            </section>

            {{-- main editing area --}}
            <section class="tab-editor__workspace" :class="{ 'is-imported-score': Boolean(tabFullMusicXml) }" aria-label="Tablature workspace"
                x-init="$nextTick(() => updateTabEditorWidth()); $watch('tabDraft', () => { if (tabScoreView === 'score') $nextTick(() => renderTabScorePreview()) }); $watch('stepForm.bpm', () => { if (tabScoreView === 'score') $nextTick(() => renderTabScorePreview()) }); $watch('stepForm.time_signature_numerator', () => { if (tabScoreView === 'score') $nextTick(() => renderTabScorePreview()) }); $watch('stepForm.time_signature_denominator', () => { if (tabScoreView === 'score') $nextTick(() => renderTabScorePreview()) }); $watch('tabSelectedIndex', () => $nextTick(() => syncTabEditorScoreCaret())); $watch('tabDraft.string', () => $nextTick(() => syncTabEditorScoreCaret())); $watch('tabInsertAtBar', () => $nextTick(() => syncTabEditorScoreCaret()))"
                @keydown="handleTabEditorKey($event)"
                @resize.window="updateTabEditorWidth()"
                @pointerup.window="endTabDrag()"
                @pointercancel.window="endTabDrag()"
            >
                <aside class="tab-editor__rhythm" x-show="!tabFullMusicXml" aria-label="Tablature tools">
                    <details class="tab-editor__palette-section" open>
                        <summary class="tab-editor__palette-heading">Rhythm<x-lucide-chevron-down aria-hidden="true" /></summary>
                        <div class="tab-editor__palette-durations" role="group" aria-label="Note duration">
                        <template x-for="item in [
                            { duration: 1, name: 'Whole', symbol: '𝅝' },
                            { duration: 2, name: 'Half', symbol: '𝅗𝅥' },
                            { duration: 4, name: 'Quarter', symbol: '♩' },
                            { duration: 8, name: 'Eighth', symbol: '♫' },
                            { duration: 16, name: 'Sixteenth', symbol: '♬' },
                            ]" :key="item.duration"
                        >
                            <button type="button" class="tab-editor__palette-tool" :class="{ 'is-active': tabDraft.duration === item.duration }" :aria-pressed="(tabDraft.duration === item.duration).toString()" :title="item.name" @click="changeTabEditorDuration(item.duration)">
                                <span aria-hidden="true" x-text="item.symbol"></span>
                                <span x-text="item.name"></span>
                            </button>
                        </template>
                    </div>

                    </details>

                    <details class="tab-editor__palette-section" open>
                        <summary class="tab-editor__palette-heading">Notation<x-lucide-chevron-down aria-hidden="true" /></summary>
                        <div class="tab-editor__palette-effects" role="group" aria-label="Notation controls">
                        <button type="button" class="tab-editor__palette-tool" :class="{ 'is-active': tabDraft.dotted }" :aria-pressed="Boolean(tabDraft.dotted).toString()" @click="setTabEditorRhythm({ dotted: !tabDraft.dotted })" title="Dotted duration: one and a half times the selected value">
                            <span aria-hidden="true">♩.</span> Dotted
                        </button>

                        <button type="button" class="tab-editor__palette-tool" :class="{ 'is-active': tabDraft.tuplet === 3 }" :aria-pressed="(tabDraft.tuplet === 3).toString()" @click="setTabEditorRhythm({ tuplet: tabDraft.tuplet === 3 ? null : 3 })" title="Triplet: three notes in the time of two">
                            <span aria-hidden="true">3:2</span> Triplet
                        </button>

                        <button type="button" class="tab-editor__palette-tool" @click="addTabEditorRest()" title="Insert or replace with a rest">
                            <span aria-hidden="true">𝄽</span> Rest
                        </button>

                        <button type="button" class="tab-editor__palette-tool"
                            :class="{ 'is-active': Boolean(tabDraft.beats[tabSelectedIndex]?.tie) }"
                            :aria-pressed="Boolean(tabDraft.beats[tabSelectedIndex]?.tie).toString()"
                            @click="toggleTabEditorTie()" title="Tie selected note to preceding note (L)">
                            <span aria-hidden="true">⌣</span> Tie
                        </button>

                        </div>
                    </details>

                    <details class="tab-editor__palette-section" open>
                        <summary class="tab-editor__palette-heading">Techniques<x-lucide-chevron-down aria-hidden="true" /></summary>
                        <div class="tab-editor__palette-effects" role="group" aria-label="Playing techniques">
                        <button type="button" class="tab-editor__palette-tool"
                            :class="{ 'is-active': Boolean(tabDraft.beats[tabSelectedIndex]?.legato) && tabEditorLegatoDirection(tabSelectedIndex) === 'H' }"
                            :aria-pressed="(Boolean(tabDraft.beats[tabSelectedIndex]?.legato) && tabEditorLegatoDirection(tabSelectedIndex) === 'H').toString()"
                            @click="toggleTabEditorLegato('H')"
                            title="Hammer-on: connect to the next higher fret on the same string">
                            <span aria-hidden="true">H</span> Hammer-on
                        </button>
                        <button type="button" class="tab-editor__palette-tool"
                            :class="{ 'is-active': Boolean(tabDraft.beats[tabSelectedIndex]?.legato) && tabEditorLegatoDirection(tabSelectedIndex) === 'P' }"
                            :aria-pressed="(Boolean(tabDraft.beats[tabSelectedIndex]?.legato) && tabEditorLegatoDirection(tabSelectedIndex) === 'P').toString()"
                            @click="toggleTabEditorLegato('P')"
                            title="Pull-off: connect to the next lower fret on the same string">
                            <span aria-hidden="true">P</span> Pull-off
                        </button>
                        <button type="button" class="tab-editor__palette-tool"
                            :class="{ 'is-active': Boolean(tabDraft.beats[tabSelectedIndex]?.palmMute) }"
                            :aria-pressed="Boolean(tabDraft.beats[tabSelectedIndex]?.palmMute).toString()"
                            @click="toggleTabEditorPalmMute()"
                            title="Toggle palm mute on selected notes (M)">
                            <span aria-hidden="true">P.M.</span> Palm mute
                        </button>
                        </div>
                    </details>
                </aside>

                <div class="tab-editor__score" x-show="!tabFullMusicXml && tabScoreView === 'grid'" x-ref="tabEditorScore" @pointerup.window="endTabDrag()" @pointercancel.window="endTabDrag()" @resize.window="updateTabEditorWidth()" tabindex="0" role="group" aria-label="Interactive six-string tablature">
                    <template x-for="(system, systemIndex) in tabEditorSystems()" :key="systemIndex">
                        <div class="tab-editor__system">
                            <div class="tab-editor__string-labels">
                                <template x-for="(name, stringIndex) in tabStringNames" :key="stringIndex">
                                    <span x-text="name"></span>
                                </template>
                            </div>

                            <div class="tab-editor__grid">
                                {{-- A stable overlay; playback time moves it continuously between notes. --}}
                                <div class="tab-editor__playhead" aria-hidden="true"></div>
                                {{-- One continuous selection area per adjacent run, behind the note cells. --}}
                                <template x-for="run in prototypeSelectionRuns(system)" :key="`${run.start}-${run.length}`">
                                    <div class="tab-editor__selection-range" aria-hidden="true"
                                        :style="`left: ${run.start * 40}px; width: ${run.length * 40}px`"></div>
                                </template>
                                <template x-for="(name, stringIndex) in tabStringNames" :key="stringIndex">
                                    <div class="tab-editor__string-row">
                                        <template x-for="column in system" :key="`${column.index}-${column.insertion}`">
                                            <button type="button" class="tab-editor__cell"
                                                :data-playhead-beat="stringIndex === 0 && !column.insertion ? column.index : null"
                                                :class="{ 'is-selected': tabSelectedIndices.length <= 1 && tabSelectedIndex === column.index && tabDraft.string === stringIndex + 1 && tabInsertAtBar === column.insertion, 'is-measure-end': column.measureEnd, 'is-insertion': column.insertion }"
                                                :aria-label="column.insertion ? `Insert note before next measure, string ${stringIndex + 1}` : `String ${stringIndex + 1}, position ${column.index + 1}`"
                                                :aria-pressed="tabSelectedIndex === column.index && tabDraft.string === stringIndex + 1 && tabInsertAtBar === column.insertion"
                                                @pointerdown="!column.insertion && startTabDrag(column.index, stringIndex + 1, $event)"
                                                @pointerenter="!column.insertion && extendTabDrag(column.index)"
                                                @click="column.insertion ? selectTabEditorInsertion(column.index, stringIndex + 1) : clickTabCell(column.index, stringIndex + 1, $event); $refs.tabEditorScoreFocus?.focus()"
                                            >
                                                <span x-text="column.insertion ? (stringIndex + 1 === tabDraft.string ? '+' : '·') : (tabEditorTieAt(column.index, stringIndex + 1) ? '(' + tabEditorNoteAt(column.index, stringIndex + 1) + ')' : (tabEditorNoteAt(column.index, stringIndex + 1) ?? '·'))"></span>
                                                <span class="tab-editor__palm-mute-mark"
                                                    :class="{ 'has-legato': tabEditorLegatoAt(column.index, stringIndex + 1) }"
                                                    x-show="!column.insertion && tabEditorPalmMuteAt(column.index, stringIndex + 1)"
                                                    x-cloak aria-hidden="true">P.M.</span>
                                                <template x-if="!column.insertion && tabEditorTieSegment(column.index, stringIndex + 1, system)">
                                                    <svg class="tab-editor__tie-arc" aria-hidden="true"
                                                        :width="tabEditorTieSegment(column.index, stringIndex + 1, system).width" height="18"
                                                        :viewBox="`0 0 ${tabEditorTieSegment(column.index, stringIndex + 1, system).width} 18`">
                                                        <path :d="`M 0 15 Q ${tabEditorTieSegment(column.index, stringIndex + 1, system).width / 2} -3 ${tabEditorTieSegment(column.index, stringIndex + 1, system).width} 15`" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    </svg>
                                                </template>
                                                <template x-if="!column.insertion && tabEditorLegatoSegment(column.index, stringIndex + 1, system)">
                                                    <svg class="tab-editor__legato-arc" aria-hidden="true"
                                                        :width="tabEditorLegatoSegment(column.index, stringIndex + 1, system).width"
                                                        height="21"
                                                        :viewBox="`0 0 ${tabEditorLegatoSegment(column.index, stringIndex + 1, system).width} 21`">
                                                        <path :d="`M 0 18 Q ${tabEditorLegatoSegment(column.index, stringIndex + 1, system).width / 2} -3 ${tabEditorLegatoSegment(column.index, stringIndex + 1, system).width} 18`" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                        <text :x="tabEditorLegatoSegment(column.index, stringIndex + 1, system).width / 2" y="8" text-anchor="middle" x-text="tabEditorLegatoSegment(column.index, stringIndex + 1, system).direction"></text>
                                                    </svg>
                                                </template>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                
                <span x-ref="tabEditorScoreFocus" class="sr-only" tabindex="-1">Tab keyboard controls</span>
                <div class="tab-editor__score-preview" x-show="!tabFullMusicXml && tabScoreView === 'score'" x-cloak
                    x-ref="tabEditorScoreKeyboard" tabindex="0" role="group" aria-label="Score keyboard editor">
                    <p class="tab-editor__score-preview-hint">Click a note to edit. Arrow keys navigate and extend the score · I inserts a position · 0–9 enters frets · R inserts a rest.</p>
                    <button type="button" class="button" data-type="outline" @click="prepareTabEditorScoreInsertion()" title="Insert a position after the current note (I)">Insert position (I)</button>
                    <button type="button" class="button" data-type="outline" @click="deleteTabEditorMeasure()" :disabled="!tabDraft.beats.length" title="Delete selected measure">Delete measure</button>
                    <p x-show="tabInsertAtBar" x-cloak>Insertion cursor at position <span x-text="tabSelectedIndex + 1"></span> · choose a duration, then type a fret or R.</p>
                    <p x-show="tabSelectedIndices.length && tabSelectedIndex < tabDraft.beats.length"
                        x-text="`Selected position ${tabSelectedIndex + 1} · string ${tabDraft.string} · use 0–9 to change fret, R for rest, H/P/M/L for techniques.`"></p>
                    <p class="tab-editor__score-preview-error" role="status" x-show="tabScorePreviewError" x-text="tabScorePreviewError"></p>
                </div>
                {{-- The playback renderer also supplies the score view; one AlphaTab instance. --}}
                <div class="tab-editor__playback-host"
                    :class="{ 'is-full-score': tabFullMusicXml || tabScoreView === 'score' }"
                    x-ref="tabEditorPreview" data-alphatab data-admin-preview="true"
                    :aria-hidden="(!tabFullMusicXml && tabScoreView !== 'score').toString()"></div>
            </section>

            {{-- Keep the validation log and playback footer docked together. --}}
            <div class="tab-editor__bottom-dock">
                {{-- Bottom-docked validation log. Uses the existing measure calculation. --}}
                <section class="tab-editor__log" x-show="!tabFullMusicXml" x-cloak aria-label="Measure validation log" x-data="{ logOpen: false }">
                    <div id="tab-editor-log-panel" class="tab-editor__log-panel" x-show="logOpen" x-cloak role="region" aria-label="Measure validation messages">
                        <div class="tab-editor__log-summary" x-text="tabEditorMeasureStatus()"></div>
                
                        <ul class="tab-editor__log-messages">
                            <template x-for="measure in tabEditorMeasureIssues()" :key="measure.number">
                                <li :data-status="measure.status">
                                    <span class="tab-editor__log-status" aria-hidden="true"></span>
                                    <span x-text="measure.message"></span>
                                </li>
                            </template>
                            <li x-show="tabDraft.beats.length > 0 && tabEditorMeasureIssues().length === 0" data-status="complete">
                                <span class="tab-editor__log-status" aria-hidden="true"></span>
                                <span>All measures are complete.</span>
                            </li>
                            <li x-show="!tabDraft.beats.length">No notes yet. Add a note to see measure validation.</li>
                        </ul>
                    </div>
                
                    <div class="tab-editor__log-bar">
                        <button type="button" class="tab-editor__log-toggle" aria-controls="tab-editor-log-panel" :aria-expanded="logOpen.toString()" @click="logOpen = !logOpen">
                            <x-lucide-square-terminal width="18" height="18" />
                            
                            <span class="fs-300">Log</span>
                            
                            <span class="tab-editor__log-count"
                                x-show="tabEditorMeasureIssues().length > 0"
                                x-text="tabEditorMeasureIssues().length"
                                aria-label="Incomplete or overfull measures"
                            ></span>
                            <x-lucide-chevron-up width="16" height="16"
                                x-bind:class="{ 'is-open': logOpen }"
                            />
                        </button>
                    </div>
                </section>

                {{-- playback controls --}}
                <footer class="tab-editor__transport">
                    <button class="button" data-type="icon" type="button"
                        :aria-label="prototypePlaying ? 'Pause' : 'Play'"
                        :class="{ 'is-active': prototypePlaying }"
                        @click="togglePrototypePlayback()" :disabled="prototypePlaybackLoading"
                    >
                        <template x-if="!prototypePlaying">
                            <x-lucide-play height="50" width="50" />
                        </template>

                        <template x-if="prototypePlaying">
                            <x-lucide-pause height="50" width="50" />
                        </template>
                    </button>

                    <div class="tab-editor__progress">
                        <div class="tab-editor__progress-track" :style="`--progress: ${prototypeProgress}%`" aria-hidden="true">
                            <span class="tab-editor__progress-active"></span>
                            <span class="tab-editor__progress-inactive"></span>
                            <span class="tab-editor__progress-handle"></span>
                            <span class="tab-editor__progress-stop"></span>
                        </div>
                        <input class="tab-editor__progress-input" type="range" min="0" max="100" step="0.1"
                            :value="prototypeProgress" :disabled="(!tabDraft.beats.length && !tabFullMusicXml) || prototypePlaybackLoading"
                            @input="previewPrototypeSeek($event.target.value)"
                            @change="seekPrototypePlayback($event.target.value)"
                            aria-label="Playback position" :aria-valuetext="`${Math.round(prototypeProgress)}% of tablature`">
                    </div>

                    <div class="tab-editor__playback-options">
                        <button class="button" data-type="icon-text" type="button" aria-label="Loop" title="Loop"
                            x-on:click="setPrototypeTransportOption('loop')"
                            x-bind:class="{ 'is-active': prototypeLoop }"
                            x-bind:aria-pressed="prototypeLoop.toString()"
                        >
                            <x-lucide-repeat />
                        </button>

                        <button class="button" data-type="icon-text" type="button" aria-label="Count-in" title="Count-in"
                            x-on:click="setPrototypeTransportOption('countIn')"
                            x-bind:class="{ 'is-active': prototypeCountIn }"
                            x-bind:aria-pressed="prototypeCountIn.toString()"
                        >
                            <x-lucide-timer />
                        </button>

                        <button class="button" data-type="icon-text" type="button" aria-label="Metronome" title="Metronome"
                            x-on:click="setPrototypeTransportOption('metronome')"
                            x-bind:class="{ 'is-active': prototypeMetronome }"
                            x-bind:aria-pressed="prototypeMetronome.toString()"
                        >
                            <x-lucide-metronome />
                        </button>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</dialog>
