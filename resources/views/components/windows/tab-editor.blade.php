{{-- Tablature editor for routine exercises. --}}
<dialog class="tab-editor-dialog" x-ref="tabEditorDialog"
    @cancel.prevent="closeNewTabEditor()" @close="tabEditorOpen = false"
    @alphatab:playback-state.window="syncPrototypePlayback($event)"
    @alphatab:playback-position.window="syncPrototypePlaybackPosition($event)"
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



            {{-- Quick settings and additional exercise information --}}
            <section class="tab-editor__utility-bar" aria-label="Exercise information"
                x-data="{ infoOpen: false, shortcutsOpen: false }"
            >
                <div class="tab-editor__utility-row">
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
                                <section class="tab-editor__shortcuts-group">
                                    <h4>Navigation &amp; selection</h4>
                                    <dl>
                                        <div><dt>Navigate notes</dt><dd>← / →</dd></div>
                                        <div><dt>Change string</dt><dd>↑ / ↓</dd></div>
                                        <div><dt>Previous / next measure</dt><dd>Ctrl/Cmd + Shift + ← / →</dd></div>
                                        <div><dt>Extend selection</dt><dd>Shift + ← / →</dd></div>
                                    </dl>
                                </section>
                                <section class="tab-editor__shortcuts-group">
                                    <h4>Note &amp; rhythm entry</h4>
                                    <dl>
                                        <div><dt>Enter fret</dt><dd>0-9</dd></div>
                                        <div><dt>Insert after</dt><dd>I</dd></div>
                                        <div><dt>Insert before</dt><dd>Shift + I</dd></div>
                                        <div><dt>Rest</dt><dd>R</dd></div>
                                        <div><dt>Change duration</dt><dd>+ / -</dd></div>
                                    </dl>
                                </section>
                                <section class="tab-editor__shortcuts-group">
                                    <h4>Techniques</h4>
                                    <dl>
                                        <div><dt>Hammer-on</dt><dd>H</dd></div>
                                        <div><dt>Pull-off</dt><dd>P</dd></div>
                                        <div><dt>Palm mute</dt><dd>M</dd></div>
                                        <div><dt>Tie</dt><dd>L</dd></div>
                                    </dl>
                                </section>
                                <section class="tab-editor__shortcuts-group">
                                    <h4>Editing</h4>
                                    <dl>
                                        <div><dt>Copy / cut / paste</dt><dd>Ctrl/Cmd + C / X / V</dd></div>
                                        <div><dt>Clear note</dt><dd>Backspace</dd></div>
                                        <div><dt>Delete position</dt><dd>Delete</dd></div>
                                        <div><dt>Undo</dt><dd>Ctrl/Cmd + Z</dd></div>
                                        <div><dt>Redo</dt><dd>Ctrl/Cmd + Shift + Z</dd></div>
                                    </dl>
                                </section>
                            </div>
                        </div>
                    </div>
                    <label class="button" data-type="secondary" data-variant="outline">
                        Import MusicXML
                        <input type="file" accept=".xml,.musicxml,application/xml,text/xml"
                            @change="importPrototypeMusicXml($event)" class="sr-only">
                    </label>
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
                        <select x-model.number="tabDraft.instrument" @change="stopPrototypePlayback()">
                            <option value="25">Steel Guitar</option>
                            <option value="28">Acoustic</option>
                            <option value="31">Jazz Clean</option>
                            <option value="29">Overdrive</option>
                            <option value="30">Distortion</option>
                        </select>
                    </label>
                </div>
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
                                >This score contains separate sections because some measures cannot be edited.</p>

                            <div x-show="(prototypeMusicXmlImport?.sections?.length ?? 0) > 1">
                                <strong>Compatible sections</strong>
                                <ul>
                                    <template x-for="(section, index) in (prototypeMusicXmlImport?.sections ?? [])" :key="index">
                                        <li x-text="`${section.firstMeasure === section.lastMeasure ? 'Measure' : 'Measures'} ${section.firstMeasure}${section.firstMeasure === section.lastMeasure ? '' : '–' + section.lastMeasure}: ${section.numerator}/${section.denominator}`"></li>
                                    </template>
                                </ul>
                            </div>

                            <p x-show="prototypeMusicXmlIssue" x-text="prototypeMusicXmlIssue"></p>
                            <template x-for="limitation in (prototypeMusicXmlImport?.limitations ?? [])" :key="limitation">
                                <p x-text="limitation"></p>
                            </template>
                            
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

            {{-- main editing area --}}
            <section class="tab-editor__workspace" :class="{ 'is-imported-score': Boolean(tabFullMusicXml) }" aria-label="Tablature workspace"
                x-init="$watch('tabDraft', () => $nextTick(() => renderTabScorePreview())); $watch('stepForm.bpm', () => $nextTick(() => renderTabScorePreview())); $watch('stepForm.time_signature_numerator', () => $nextTick(() => renderTabScorePreview())); $watch('stepForm.time_signature_denominator', () => $nextTick(() => renderTabScorePreview())); $watch('tabSelectedIndex', () => $nextTick(() => syncTabEditorScoreCaret())); $watch('tabSelectedIndices', () => $nextTick(() => syncTabEditorScoreCaret())); $watch('tabDraft.string', () => $nextTick(() => syncTabEditorScoreCaret())); $watch('tabInsertAtBar', () => $nextTick(() => syncTabEditorScoreCaret()))"
                @keydown="handleTabEditorKey($event)"
            >
                <div class="tab-editor__edit-toolbar" role="group" aria-label="Score controls">
                    <div class="tab-editor__quick-settings">
                        <label class="tab-editor__field">
                            <span>BPM</span>
                            <input type="number" min="30" max="400" :value="tabEditorCurrentBpm()"
                                @change="changeTabEditorBpm($event.target.value); $event.target.value = tabEditorCurrentBpm()"
                                :title="tabFullMusicXml ? 'Initial playback tempo' : 'Tempo from selected measure'">
                        </label>

                        <button type="button" class="button" data-type="outline"
                            x-show="!tabFullMusicXml && tabEditorHasTempoChange()" x-cloak
                            @click="removeTabEditorTempoChange()" title="Remove tempo change and inherit previous tempo"
                            aria-label="Remove tempo change">
                            <x-lucide-x width="16" height="16" aria-hidden="true" />
                        </button>

                        <div class="tab-editor__field">
                            <span>Time signature</span>
                            <div class="tab-editor__time-signature">
                                <select :value="tabEditorCurrentSignature().numerator" @change="changeTabEditorSignature($event.target.value, tabEditorCurrentSignature().denominator)" aria-label="Beats per measure">
                                    @foreach (range(1, 16) as $numerator)
                                        <option value="{{ $numerator }}">{{ $numerator }}</option>
                                    @endforeach
                                </select>
                                <span aria-hidden="true">/</span>
                                <select :value="tabEditorCurrentSignature().denominator" @change="changeTabEditorSignature(tabEditorCurrentSignature().numerator, $event.target.value)" aria-label="Beat note value">
                                    <option value="2">2</option>
                                    <option value="4">4</option>
                                    <option value="8">8</option>
                                    <option value="16">16</option>
                                </select>
                            </div>
                        </div>
                        <button type="button" class="button" data-type="outline"
                            x-show="!tabFullMusicXml && tabEditorHasSignatureChange()" x-cloak
                            @click="removeTabEditorSignatureChange()" title="Remove time signature change"
                            aria-label="Remove time signature change">
                            <x-lucide-x width="16" height="16" aria-hidden="true" />
                        </button>
                    </div>
                    <div class="tab-editor__edit-actions" x-show="!tabFullMusicXml" x-cloak role="group" aria-label="Editing tools">
                    <div class="tab-editor__edit-group" role="group" aria-label="History">
                        <button type="button" class="tab-editor__edit-tool" aria-label="Undo" title="Undo (Ctrl/Cmd + Z)"
                            :disabled="prototypePlaying || prototypePlaybackLoading || !tabUndoHistory.length" @click="undoTabEditorChange(); $nextTick(() => $refs.tabEditorScoreKeyboard?.focus({ preventScroll: true }))">
                            <x-lucide-undo-2 width="20" height="20" aria-hidden="true" />
                        </button>
                        <button type="button" class="tab-editor__edit-tool" aria-label="Redo" title="Redo (Ctrl/Cmd + Shift + Z)"
                            :disabled="prototypePlaying || prototypePlaybackLoading || !tabRedoHistory.length" @click="redoTabEditorChange(); $nextTick(() => $refs.tabEditorScoreKeyboard?.focus({ preventScroll: true }))">
                            <x-lucide-redo-2 width="20" height="20" aria-hidden="true" />
                        </button>
                    </div>
                    <div class="tab-editor__edit-group" role="group" aria-label="Clipboard">
                        <button type="button" class="tab-editor__edit-tool" aria-label="Cut" title="Cut (Ctrl/Cmd + X)"
                            :disabled="prototypePlaying || prototypePlaybackLoading || tabInsertAtBar || tabSelectedIndex >= tabDraft.beats.length" @click="copyTabEditorSelection(true); $nextTick(() => $refs.tabEditorScoreKeyboard?.focus({ preventScroll: true }))">
                            <x-lucide-scissors width="20" height="20" aria-hidden="true" />
                        </button>
                        <button type="button" class="tab-editor__edit-tool" aria-label="Copy" title="Copy (Ctrl/Cmd + C)"
                            :disabled="prototypePlaying || prototypePlaybackLoading || tabInsertAtBar || tabSelectedIndex >= tabDraft.beats.length" @click="copyTabEditorSelection(); $nextTick(() => $refs.tabEditorScoreKeyboard?.focus({ preventScroll: true }))">
                            <x-lucide-copy width="20" height="20" aria-hidden="true" />
                        </button>
                        <button type="button" class="tab-editor__edit-tool" aria-label="Paste" title="Paste (Ctrl/Cmd + V)"
                            :disabled="prototypePlaying || prototypePlaybackLoading || !tabClipboard.length" @click="pasteTabEditorSelection(); $nextTick(() => $refs.tabEditorScoreKeyboard?.focus({ preventScroll: true }))">
                            <x-lucide-clipboard-paste width="20" height="20" aria-hidden="true" />
                        </button>
                    </div>
                    <div class="tab-editor__edit-group" role="group" aria-label="Structure">
                        <button type="button" class="tab-editor__edit-tool" aria-label="Insert position" title="Insert after (I) · Insert before (Shift + I)"
                            :disabled="prototypePlaying || prototypePlaybackLoading" @click="prepareTabEditorScoreInsertion(); $nextTick(() => $refs.tabEditorScoreKeyboard?.focus({ preventScroll: true }))">
                            <x-lucide-list-plus width="20" height="20" aria-hidden="true" />
                        </button>
                        <button type="button" class="tab-editor__edit-tool" aria-label="Delete measure" title="Delete measure"
                            :disabled="prototypePlaying || prototypePlaybackLoading || !tabDraft.beats.length" @click="deleteTabEditorMeasure(); $nextTick(() => $refs.tabEditorScoreKeyboard?.focus({ preventScroll: true }))">
                            <x-lucide-trash-2 width="20" height="20" aria-hidden="true" />
                        </button>
                    </div>
                    </div>
                </div>

                <aside class="tab-editor__rhythm" x-show="!tabFullMusicXml" aria-label="Tablature tools"
                    x-data="{
                        paletteObserver: null,
                        updatePaletteHeight: null,
                        paletteDialog: null,
                        init() {
                            const palette = this.$el;
                            const dialog = palette.closest('dialog');
                            const dock = palette.closest('.tab-editor').querySelector('.tab-editor__bottom-dock');
                            this.paletteDialog = dialog;
                            this.updatePaletteHeight = () => {
                                if (!palette.getClientRects().length) return;
                                const top = Math.max(palette.getBoundingClientRect().top, dialog.getBoundingClientRect().top, 0);
                                const bottom = Math.min(dock.getBoundingClientRect().top, dialog.getBoundingClientRect().bottom, window.innerHeight);
                                palette.style.setProperty('--tab-editor-palette-height', Math.max(0, bottom - top) + 'px');
                            };
                            this.paletteObserver = new ResizeObserver(this.updatePaletteHeight);
                            this.paletteObserver.observe(dialog);
                            this.paletteObserver.observe(dock);
                            this.paletteObserver.observe(palette.closest('.tab-editor__workspace').querySelector('.tab-editor__edit-toolbar'));
                            this.paletteObserver.observe(palette.closest('.tab-editor').querySelector('.tab-editor__utility-bar'));
                            dialog.addEventListener('scroll', this.updatePaletteHeight, { passive: true });
                            window.addEventListener('resize', this.updatePaletteHeight);
                            this.$nextTick(this.updatePaletteHeight);
                        },
                        destroy() {
                            this.paletteObserver?.disconnect();
                            this.paletteDialog?.removeEventListener('scroll', this.updatePaletteHeight);
                            window.removeEventListener('resize', this.updatePaletteHeight);
                        }
                    }">
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
                        <template x-for="stroke in [{ code: 'sd', name: 'Downstroke', symbol: '⊓' }, { code: 'su', name: 'Upstroke', symbol: 'V' }]" :key="stroke.code">
                            <button type="button" class="tab-editor__palette-tool"
                                :class="{ 'is-active': tabDraft.beats[tabSelectedIndex]?.pickStroke === stroke.code }"
                                :aria-pressed="(tabDraft.beats[tabSelectedIndex]?.pickStroke === stroke.code).toString()"
                                :title="stroke.name" @click="toggleTabEditorPickStroke(stroke.code)">
                                <span aria-hidden="true" x-text="stroke.symbol"></span>
                                <span x-text="stroke.name"></span>
                            </button>
                        </template>
                        <div class="tab-editor__slide-tool" x-data="{
                            slideOpen: false,
                            selectedSlide: 'ss',
                            slides: [
                                { code: 'ss', name: 'Slide', symbol: '/' },
                                { code: 'sl', name: 'Legato slide', symbol: '⌣/' },
                                { code: 'sib', name: 'Slide in from below', symbol: '↗' },
                                { code: 'sia', name: 'Slide in from above', symbol: '↘' },
                                { code: 'sou', name: 'Slide out upwards', symbol: '↗' },
                                { code: 'sod', name: 'Slide out downwards', symbol: '↘' }
                            ]
                        }" @click.outside="slideOpen = false" @keydown.escape.stop="slideOpen = false"
                            @scroll.window.capture="if (slideOpen && $event.target !== $refs.slideOptions) slideOpen = false"
                            @resize.window="slideOpen = false">
                            <div class="tab-editor__slide-split">
                                <button type="button" class="tab-editor__palette-tool"
                                    :class="{ 'is-active': tabDraft.beats[tabSelectedIndex]?.slide === selectedSlide }"
                                    :aria-pressed="(tabDraft.beats[tabSelectedIndex]?.slide === selectedSlide).toString()"
                                    :title="slides.find(slide => slide.code === selectedSlide).name"
                                    @click="toggleTabEditorSlide(selectedSlide)">
                                    <span aria-hidden="true" x-text="slides.find(slide => slide.code === selectedSlide).symbol"></span>
                                    <span x-text="slides.find(slide => slide.code === selectedSlide).name"></span>
                                </button>
                                <button type="button" class="tab-editor__slide-toggle" aria-label="Choose slide type"
                                    aria-controls="tab-editor-slide-options" :aria-expanded="slideOpen.toString()"
                                    @click="slideOpen = !slideOpen; if (slideOpen) $nextTick(() => {
                                        const trigger = $el.parentElement.getBoundingClientRect();
                                        const dialog = $el.closest('dialog').getBoundingClientRect();
                                        const dock = $el.closest('.tab-editor').querySelector('.tab-editor__bottom-dock').getBoundingClientRect();
                                        const menu = $refs.slideOptions;
                                        const top = Math.max(dialog.top, 0);
                                        const bottom = Math.min(dialog.bottom, window.innerHeight, dock.top);
                                        const left = Math.max(dialog.left, 0);
                                        const right = Math.min(dialog.right, window.innerWidth);
                                        menu.style.maxWidth = Math.max(0, right - left) + 'px';
                                        menu.style.removeProperty('max-height');
                                        const above = Math.max(0, trigger.top - top);
                                        const below = Math.max(0, bottom - trigger.bottom);
                                        const opensAbove = menu.scrollHeight > below && above > below;
                                        menu.style.maxHeight = (opensAbove ? above : below) + 'px';
                                        menu.style.left = Math.max(left, Math.min(trigger.left, right - menu.offsetWidth)) + 'px';
                                        menu.style.top = (opensAbove ? trigger.top - menu.offsetHeight : trigger.bottom) + 'px';
                                    })">
                                    <x-lucide-chevron-down width="16" height="16" aria-hidden="true" />
                                </button>
                            </div>
                            <div id="tab-editor-slide-options" class="tab-editor__slide-options" x-ref="slideOptions" x-show="slideOpen" x-cloak role="group" aria-label="Slide types">
                                <template x-for="slide in slides" :key="slide.code">
                                    <button type="button" class="tab-editor__palette-tool"
                                        :aria-pressed="(selectedSlide === slide.code).toString()"
                                        @click="selectedSlide = slide.code; slideOpen = false">
                                        <span aria-hidden="true" x-text="slide.symbol"></span>
                                        <span x-text="slide.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        </div>
                    </details>
                </aside>

                <div class="tab-editor__score-preview" x-show="!tabFullMusicXml" x-cloak
                    x-ref="tabEditorScoreKeyboard" tabindex="0" role="group" aria-label="Score keyboard editor">
                    <p class="tab-editor__score-preview-error" role="status" x-show="tabScorePreviewError" x-text="tabScorePreviewError"></p>
                </div>
                {{-- The playback renderer also supplies the score view; one AlphaTab instance. --}}
                <div class="tab-editor__playback-host is-full-score"
                    
                    x-ref="tabEditorPreview" data-alphatab data-admin-preview="true"  :data-score-editable="(!tabFullMusicXml).toString()"></div>
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
