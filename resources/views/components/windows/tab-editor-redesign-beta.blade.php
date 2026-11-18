{{-- Opt-in real-routine editor; legacy editor remains the default. --}}
<dialog class="tab-editor-beta" x-ref="tabEditorDialog"
    @cancel.prevent="closeNewTabEditor()" @close="tabEditorOpen = false"
    @alphatab:playback-state.window="syncPrototypePlayback($event)"
    @alphatab:playback-position.window="syncPrototypePlaybackPosition($event)"
    @alphatab:played-beat.window="syncPrototypePlayedBeat($event)"
    @alphatab:playback-finished.window="finishPrototypePlayback($event)"
    @alphatab:load-error.window="prototypePlaybackError($event)">
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

            <div class="tab-editor__import-panel" x-show="prototypeMusicXmlImport" x-cloak>
                <p x-text="`${prototypeMusicXmlImport?.sections.length ?? 0} editable section(s) found. Select a section to replace the current unsaved score.`"></p>

                <label class="tab-editor__field">
                    <span>Section</span>
                    <select x-model.number="prototypeMusicXmlSelectedSection">
                        <template x-for="(section, index) in (prototypeMusicXmlImport?.sections ?? [])" :key="index">
                            <option :value="index" x-text="`Measures ${section.firstMeasure}–${section.lastMeasure} · ${section.numerator}/${section.denominator}`"></option>
                        </template>
                    </select>
                </label>

                <div class="tab-editor__import-warnings" x-show="prototypeMusicXmlImport?.warnings.length" x-cloak>
                    <p>These measures are not compatible with the simple tablature editor:</p>
                    <ul>
                        <template x-for="(warning, index) in (prototypeMusicXmlImport?.warnings ?? [])" :key="index">
                            <li x-text="warning"></li>
                        </template>
                    </ul>
                </div>

                <div class="tab-editor__import-actions">
                    <button class="button" data-type="primary" type="button"
                        @click="applyPrototypeMusicXmlSection()">Use selected section</button>
                    <button class="button" data-type="outline" type="button"
                        @click="prototypeMusicXmlImport = null">Dismiss</button>
                </div>
                <small>Only editable guitar tablature sections can be imported in this editor.</small>
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
        <section class="tab-editor__workspace" aria-label="Tablature workspace" x-init="$nextTick(() => updateTabEditorWidth())"
            @keydown="handleTabEditorKey($event)"
            @resize.window="updateTabEditorWidth()"
            @pointerup.window="endTabDrag()"
            @pointercancel.window="endTabDrag()"
        >
            <aside class="tab-editor__rhythm" aria-label="Rhythm tools">
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

                <div class="tab-editor__palette-effects" role="group" aria-label="Rhythm modifiers">
                    <button type="button" class="tab-editor__palette-tool" :class="{ 'is-active': tabDraft.dotted }" :aria-pressed="Boolean(tabDraft.dotted).toString()" @click="setTabEditorRhythm({ dotted: !tabDraft.dotted })" title="Dotted duration: one and a half times the selected value">
                        <span aria-hidden="true">♩.</span> Dotted
                    </button>

                    <button type="button" class="tab-editor__palette-tool" :class="{ 'is-active': tabDraft.tuplet === 3 }" :aria-pressed="(tabDraft.tuplet === 3).toString()" @click="setTabEditorRhythm({ tuplet: tabDraft.tuplet === 3 ? null : 3 })" title="Triplet: three notes in the time of two">
                        <span aria-hidden="true">3:2</span> Triplet
                    </button>

                    <button type="button" class="tab-editor__palette-tool" @click="addTabEditorRest()" title="Insert or replace with a rest">
                        <span aria-hidden="true">𝄽</span> Rest
                    </button>
                </div>
            </aside>

            <div class="tab-editor__score" x-ref="tabEditorScore" @pointerup.window="endTabDrag()" @pointercancel.window="endTabDrag()" @resize.window="updateTabEditorWidth()" tabindex="0" role="group" aria-label="Interactive six-string tablature">
                <template x-for="(system, systemIndex) in tabEditorSystems()" :key="systemIndex">
                    <div class="tab-editor__system">
                        <div class="tab-editor__string-labels">
                            <template x-for="(name, stringIndex) in tabStringNames" :key="stringIndex">
                                <span x-text="name"></span>
                            </template>
                        </div>

                        <div class="tab-editor__grid">
                            <template x-if="system.some(column => !column.insertion && column.index === prototypePlayingBeatIndex)">
                                <div class="tab-editor__playhead" aria-hidden="true"
                                    :style="`left: ${Math.max(0, system.findIndex(column => !column.insertion && column.index === prototypePlayingBeatIndex)) * 40}px`"></div>
                            </template>
                            <template x-for="(name, stringIndex) in tabStringNames" :key="stringIndex">
                                <div class="tab-editor__string-row">
                                    <template x-for="column in system" :key="`${column.index}-${column.insertion}`">
                                        <button type="button" class="tab-editor__cell"
                                            :class="{ 'is-selected': tabSelectedIndex === column.index && tabDraft.string === stringIndex + 1 && tabInsertAtBar === column.insertion, 'is-measure-end': column.measureEnd, 'is-multi-selected': !column.insertion && tabSelectedIndices.includes(column.index) && column.index < tabDraft.beats.length , 'is-insertion': column.insertion }"
                                            :aria-label="column.insertion ? `Insert note before next measure, string ${stringIndex + 1}` : `String ${stringIndex + 1}, position ${column.index + 1}`"
                                            :aria-pressed="tabSelectedIndex === column.index && tabDraft.string === stringIndex + 1 && tabInsertAtBar === column.insertion"
                                            @pointerdown="!column.insertion && startTabDrag(column.index, stringIndex + 1, $event)"
                                            @pointerenter="!column.insertion && extendTabDrag(column.index)"
                                            @click="column.insertion ? selectTabEditorInsertion(column.index, stringIndex + 1) : clickTabCell(column.index, stringIndex + 1, $event); $refs.tabEditorScoreFocus?.focus()"
                                            x-text="column.insertion ? (stringIndex + 1 === tabDraft.string ? '+' : '·') : (tabEditorNoteAt(column.index, stringIndex + 1) ?? '·')"
                                        >
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            
            <span x-ref="tabEditorScoreFocus" class="sr-only" tabindex="-1">Tab keyboard controls</span>

            {{-- Bottom-docked validation log. Uses the existing measure calculation. --}}
            <section class="tab-editor__log" aria-label="Measure validation log" x-data="{ logOpen: false }">
                <div id="tab-editor-log-panel" class="tab-editor__log-panel" x-show="logOpen" x-cloak role="region" aria-label="Measure validation messages">
                    <div class="tab-editor__log-summary" x-text="tabEditorMeasureStatus()"></div>

                    <ul class="tab-editor__log-messages">
                        <template x-for="measure in tabEditorMeasureWarnings()" :key="measure.number">
                            <li :data-status="measure.status">
                                <span class="tab-editor__log-status" aria-hidden="true"></span>
                                <span x-text="measure.message"></span>
                            </li>
                        </template>
                        <li x-show="!tabDraft.beats.length">No notes yet. Add a note to see measure validation.</li>
                    </ul>
                </div>

                <div class="tab-editor__log-bar">
                    <button type="button" class="tab-editor__log-toggle" aria-controls="tab-editor-log-panel" :aria-expanded="logOpen.toString()" @click="logOpen = !logOpen">
                        <x-lucide-square-terminal width="18" height="18" />
                        
                        <span class="fs-300">Log</span>
                        
                        <span class="tab-editor__log-count"
                            x-show="tabEditorMeasureWarnings().some(measure => measure.status !== 'complete')"
                            x-text="tabEditorMeasureWarnings().filter(measure => measure.status !== 'complete').length"
                            aria-label="Incomplete or overfull measures"
                        ></span>
                        <x-lucide-chevron-up width="16" height="16"
                            x-bind:class="{ 'is-open': logOpen }"
                        />
                    </button>
                </div>
            </section>
        </section>

        {{-- Playback uses the existing alphaTab engine without replacing the editable grid. --}}
        <div class="tab-editor__playback-host" x-ref="tabEditorPreview" data-alphatab data-admin-preview="true" aria-hidden="true"></div>

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
                    :value="prototypeProgress" :disabled="!tabDraft.beats.length || prototypePlaybackLoading"
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
</dialog>
