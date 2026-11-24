import {
    createTabDraft, appendTabNote, serializeTabDraft, parseTabDraft,
    layoutTabMeasures, isTabFullMeasureRest, selectTabNoteIndices, copyTabFragment, pasteTabFragment, changeSelectedTabDurations,
    changeSelectedTabRhythm, tabBeatUnits, clearTabNotes, updateTabBreaks,
    breaksForInsertedTabNote, tabBeatNotes, setTabBeatNote,
    removeTabNoteAtString, tabLegatoDirection, toggleTabLegato, normalizeTabLegato, toggleTabPalmMute, toggleTabTie, normalizeTabTies, TAB_DURATIONS
} from "./alphatab/tab-editor.js";

// The Alpine component spreads these actions into its existing exercise state.
// Keeping method context intact avoids any change to user-facing behavior.
export function tablatureEditorMethods() {
    return {
        openNewTabEditor() {
            if (!this.canCreateTablatures) return;
            if (this.stepFormMode !== "create" || this.steps.length >= this.maxSteps) return;
            this.isStepFormOpen = false;
            this.$refs.stepDialog.close();
            this.tabEditorEditingIndex = null;
            this.tabDraft = createTabDraft();
            this.tabUndoHistory = [];
            this.tabRedoHistory = [];
            this.tabFullMusicXml = null;
            this.tabSelectedIndex = 0;
            this.tabInsertAtBar = false;
            this.tabSelectedIndices = [];
            this.tabSelectionAnchor = null;
            this.tabEditorOpen = true;
            this.prepareRedesignEditor();
            this.$nextTick(() => {
                this.$refs.tabEditorDialog?.showModal();
                this.initializeTabScoreView();
            });
        },

        openSavedTabEditor(index, viewerDialog = null) {
            if (!this.canCreateTablatures) return;
            const step = this.steps[index];
            if (!step || step.tab_source !== "user" || !step.alpha_tex || step.musicxml_source) return;
            let parsed;
            try {
                parsed = parseTabDraft(step.alpha_tex);
            } catch (error) {
                this.showToast?.(error.message, "error");
                return;
            }
            this.tabEditorEditingIndex = index;
            this.tabDraft = parsed.draft;
            this.tabUndoHistory = [];
            this.tabRedoHistory = [];
            this.tabFullMusicXml = null;
            this.tabSelectedIndex = parsed.draft.beats.length;
            this.tabInsertAtBar = false;
            this.tabSelectedIndices = [];
            this.tabSelectionAnchor = null;
            this.stepForm = {
                name: step.name,
                bpm: parsed.bpm,
                time_signature_numerator: parsed.numerator,
                time_signature_denominator: parsed.denominator,
                mode: step.mode,
                duration_seconds: Number(step.duration_seconds ?? 60),
            };
            this.stepFormMinutes = Math.floor(this.stepForm.duration_seconds / 60);
            this.stepFormSeconds = this.stepForm.duration_seconds % 60;
            // Editing takes over from practice: stop the timer and alphaTab
            // before closing the viewer or opening the editor.
            if (this.isPlaying) {
                this.stop("manual");
            } else {
                window.dispatchEvent(new Event("alphatab:stop"));
            }
            viewerDialog?.close();
            this.tabEditorOpen = true;
            this.prepareRedesignEditor();
            this.$nextTick(() => {
                this.$refs.tabEditorDialog?.showModal();
                this.initializeTabScoreView();
            });
        },

        closeNewTabEditor() {
            this.stopPrototypePlayback();
            this.finishPendingTabDigit(false);
            this.$refs.tabEditorDialog?.close();
            this.tabEditorOpen = false;
            this.tabUndoHistory = [];
            this.tabRedoHistory = [];
            this.tabEditorEditingIndex = null;
            this.tabFullMusicXml = null;
        },

        appendTabEditorMeasure(recordUndo = true) {
            this.finishPendingTabDigit(false);
            if (recordUndo) this.recordTabEditorUndo();
            const index = this.tabDraft.beats.length;
            const beats = [...this.tabDraft.beats, { rest: true, duration: 1 }];
            const breaks = index ? [...this.tabDraft.breaks, index] : [];
            this.tabDraft = { ...this.tabDraft, beats, breaks };
            this.selectTabEditorCell(index, this.tabDraft.string);
            this.$nextTick(() => this.$refs.tabEditorScoreKeyboard?.focus());
        },

        // Resolve insertion slots consistently with the score caret.
        tabEditorActiveMeasureIndex(measures = this.tabEditorMeasureLayout()) {
            if (this.tabInsertAtBar && this.tabSelectedIndex > 0) {
                const previous = measures.findIndex(measure => measure.endIndex === this.tabSelectedIndex - 1);
                if (previous >= 0 && measures[previous].used + tabBeatUnits(this.tabDraft) <= measures[previous].capacity + 1e-7) return previous;
            }
            const index = Math.min(this.tabSelectedIndex, this.tabDraft.beats.length - 1);
            return measures.findIndex(measure => measure.notes.some(note => note.index === index));
        },

        deleteTabEditorMeasure() {
            const measures = this.tabEditorMeasureLayout();
            const measure = measures[this.tabEditorActiveMeasureIndex(measures)];
            if (!measure) return;
            this.finishPendingTabDigit(false);
            this.recordTabEditorUndo();
            const start = measure.notes[0].index;
            const length = measure.notes.length;
            const beats = [...this.tabDraft.beats];
            beats.splice(start, length);
            const breaks = [...new Set(this.tabDraft.breaks
                .filter(position => position < start || position > measure.endIndex)
                .map(position => position > measure.endIndex ? position - length : position)
                .filter(position => position > 0 && position < beats.length))];
            if (!beats.length) beats.push({ rest: true, duration: 1 });
            this.tabDraft = { ...this.tabDraft, beats: normalizeTabTies(normalizeTabLegato(beats)), breaks };
            this.selectTabEditorCell(Math.min(start, beats.length - 1), this.tabDraft.string);
            this.$nextTick(() => this.$refs.tabEditorScoreKeyboard?.focus());
        },

        advanceTabEditorPosition(index, recordUndo = true) {
            const next = Math.min(this.tabDraft.beats.length, index + 1);
            this.tabSelectedIndex = next;
            this.tabSelectedIndices = [];
            this.tabSelectionAnchor = null;
            this.tabInsertAtBar = false;
            const measure = this.tabEditorMeasureLayout().find(item => item.endIndex === index);
            // At a bar boundary, keep the insertion slot in the preceding
            // measure while the chosen rhythm still fits.
            if (measure && measure.used + tabBeatUnits(this.tabDraft) <= measure.capacity + 1e-7) {
                this.tabInsertAtBar = true;
            } else if (next < this.tabDraft.beats.length) {
                this.selectTabEditorCell(next, this.tabDraft.string);
            } else {
                this.appendTabEditorMeasure(recordUndo);
            }
        },

        prepareTabEditorScoreInsertion() {
            if (this.tabFullMusicXml) return;
            this.finishPendingTabDigit(false);
            // An insertion cursor is not a note. Keep the current rhythm and
            // let addTabEditorNote/addTabEditorRest reuse the existing model.
            const afterSelected = this.tabSelectedIndices.length > 0;
            const index = Math.min(this.tabDraft.beats.length,
                this.tabSelectedIndex + (afterSelected ? 1 : 0));
            this.tabSelectedIndex = index;
            this.tabInsertAtBar = true;
            this.tabSelectedIndices = [];
            this.tabSelectionAnchor = null;
            this.$nextTick(() => this.$refs.tabEditorScoreKeyboard?.focus());
        },

        selectTabEditorScoreBeat(event) {
            const detail = event?.detail;
            if (!detail || detail.element !== this.$refs.tabEditorPreview ||
                this.tabFullMusicXml) return;
            const measureIndex = Number(detail.measureIndex);
            const beatIndex = Number(detail.beatIndex);
            if (!Number.isInteger(measureIndex) || !Number.isInteger(beatIndex)) return;
            const measure = this.tabEditorMeasureLayout()[measureIndex];
            const position = measure?.notes?.[beatIndex]?.index;
            if (!Number.isInteger(position)) return;
            const notes = tabBeatNotes(this.tabDraft.beats[position]);
            const requestedString = Number(detail.string);
            const string = Number.isInteger(requestedString) && requestedString >= 1 && requestedString <= 6
                ? requestedString : (notes[0]?.string ?? this.tabDraft.string);
            this.selectTabEditorCell(position, string, detail);
            this.$nextTick(() => this.$refs.tabEditorScoreKeyboard?.focus());
        },

        selectTabEditorInsertion(index, string) {
            const previousBreak = Math.max(0, ...(this.tabDraft.breaks ?? []).filter(value => value < index));
            const used = this.tabDraft.beats.slice(previousBreak, index)
                .reduce((total, beat) => total + tabBeatUnits(beat), 0);
            const available = Number(this.stepForm.time_signature_numerator) * 16 / Number(this.stepForm.time_signature_denominator) - used;
            this.selectTabEditorCell(index, string);
            this.tabInsertAtBar = true;
            this.tabSelectedIndices = [];
            this.tabSelectionAnchor = null;
            // A virtual insertion point is not an existing note: choose a
            // duration that fits, without changing any saved note's duration.
            if (tabBeatUnits(this.tabDraft) > available + 1e-7) {
                this.tabDraft = { ...this.tabDraft, duration: [1, 2, 4, 8, 16]
                    .find(duration => 16 / duration <= available) ?? 16 };
            }
        },

        tabEditorMeasureLayout() {
            return layoutTabMeasures(this.tabDraft, this.stepForm.time_signature_numerator, this.stepForm.time_signature_denominator);
        },

        tabEditorMeasureStatus() {
            const measures = this.tabEditorMeasureLayout();
            if (!measures.length) return 'Measure 1 · empty';
            const invalid = measures.filter(measure => !measure.complete).length;
            return invalid === 0
                ? `${measures.length} measure(s) · all complete`
                : `${measures.length} measure(s) · ${invalid} incomplete or overfull`;
        },

        tabEditorMeasureWarnings() {
            return this.tabEditorMeasureLayout().map((measure, index) => ({
                number: index + 1,
                status: measure.complete ? 'complete' : measure.used > measure.capacity ? 'overflow' : 'incomplete',
                message: measure.complete
                    ? `Measure ${index + 1}: complete`
                    : measure.used > measure.capacity
                        ? `Measure ${index + 1}: ${Number((measure.used - measure.capacity).toFixed(3))} sixteenth-note units over limit`
                        : `Measure ${index + 1}: ${Number((measure.capacity - measure.used).toFixed(3))} sixteenth-note units remaining`,
            }));
        },

        tabEditorMeasureIssues() {
            return this.tabEditorMeasureWarnings().filter(measure => measure.status !== 'complete');
        },

        toggleTabEditorTie() {
            this.finishPendingTabDigit(false);
            if (this.tabInsertAtBar) return;
            try {
                const beats = toggleTabTie(this.tabDraft.beats, this.tabSelectedIndex);
                this.recordTabEditorUndo();
                this.tabDraft = { ...this.tabDraft, beats };
            } catch (error) {
                this.showToast?.(error.message, 'error');
            }
        },

        toggleTabEditorPalmMute() {
            this.finishPendingTabDigit(false);
            if (this.tabInsertAtBar) return;
            const selected = this.tabSelectedIndices.length
                ? this.tabSelectedIndices
                : [this.tabSelectedIndex];
            const beats = toggleTabPalmMute(this.tabDraft.beats, selected);
            if (beats === this.tabDraft.beats) return;
            this.recordTabEditorUndo();
            this.tabDraft = { ...this.tabDraft, beats };
        },

        tabEditorLegatoDirection(index) {
            return tabLegatoDirection(this.tabDraft.beats, index);
        },

        toggleTabEditorLegato(technique) {
            this.finishPendingTabDigit(false);
            const index = this.tabSelectedIndex;
            if (this.tabInsertAtBar) return;
            try {
                const beats = toggleTabLegato(this.tabDraft.beats, index, technique);
                this.recordTabEditorUndo();
                this.tabDraft = { ...this.tabDraft, beats: normalizeTabTies(normalizeTabLegato(beats)) };
            } catch (error) {
                this.showToast?.(error.message, 'error');
            }
        },

        selectTabEditorCell(index, string, event = null) {
            const validNote = index < this.tabDraft.beats.length;
            if (event?.shiftKey && validNote) {
                if (!Number.isInteger(this.tabSelectionAnchor)) {
                    this.tabSelectionAnchor = Math.max(0, Math.min(this.tabSelectedIndex, this.tabDraft.beats.length - 1));
                }
                this.tabSelectedIndices = selectTabNoteIndices(this.tabSelectedIndices, this.tabSelectionAnchor, index, 'range', this.tabDraft.beats.length);
            } else if ((event?.ctrlKey || event?.metaKey) && validNote) {
                this.tabSelectedIndices = selectTabNoteIndices(this.tabSelectedIndices, this.tabSelectionAnchor, index, 'toggle', this.tabDraft.beats.length);
                this.tabSelectionAnchor = index;
            } else {
                this.tabSelectedIndices = validNote ? [index] : [];
                this.tabSelectionAnchor = validNote ? index : null;
            }
            this.tabSelectedIndex = index;
            this.tabInsertAtBar = false;
            this.finishPendingTabDigit(false);
            const note = this.tabDraft.beats[index];
            const fullRest = note?.rest &&
                this.tabEditorMeasureLayout().some(measure =>
                    measure.endIndex === index && isTabFullMeasureRest(measure.notes));
            this.tabDraft = {
                ...this.tabDraft,
                string,
                fret: tabBeatNotes(note).find(n => n.string === string)?.fret ?? 0,
                duration: fullRest ? this.tabDraft.duration : (note?.duration ?? this.tabDraft.duration),
                dotted: note && !fullRest ? Boolean(note.dotted) : Boolean(this.tabDraft.dotted),
                tuplet: note && !fullRest ? (note.tuplet ?? null) : this.tabDraft.tuplet,
            };
        },

        finishPendingTabDigit(advance = true) {
            const pending = this.tabDigitPending;
            clearTimeout(this.tabDigitTimer);
            this.tabDigitTimer = null;
            this.tabDigitPending = null;
            if (advance && pending && this.tabSelectedIndex === pending.index) {
                this.advanceTabEditorPosition(pending.index, false);
            }
        },

        enterTabFretDigit(digit) {
            const pending = this.tabDigitPending;
            if (pending && this.tabSelectedIndex === pending.index) {
                const fret = pending.digit * 10 + digit;
                if (fret >= 10 && fret <= 24) {
                    this.recordTabEditorUndo();
                    this.tabDraft = {
                        ...this.tabDraft,
                        beats: normalizeTabLegato(this.tabDraft.beats.map((note, index) =>
                            index === pending.index ? setTabBeatNote(note, fret, pending.string) : note
                        )),
                        fret,
                    };
                    this.finishPendingTabDigit();
                    return;
                }
                // 25-29 are invalid frets: treat the second key as a new note.
                this.finishPendingTabDigit();
            }
            const index = this.tabSelectedIndex;
            this.tabDraft = { ...this.tabDraft, fret: digit };
            this.addTabEditorNote(digit === 1 || digit === 2);
            if (digit === 1 || digit === 2) {
                // Keep the caret on the first digit until it is completed or expires.
                this.tabSelectedIndex = index;
                this.tabSelectedIndices = [index];
                this.tabSelectionAnchor = index;
                const entry = { digit, index, string: this.tabDraft.string };
                this.tabDigitPending = entry;
                clearTimeout(this.tabDigitTimer);
                this.tabDigitTimer = setTimeout(() => {
                    if (this.tabDigitPending === entry) this.finishPendingTabDigit();
                }, 650);
            }
        },

        tabEditorSnapshot() {
            return {
                draft: JSON.parse(JSON.stringify(this.tabDraft)),
                selectedIndex: this.tabSelectedIndex,
                selectedIndices: [...this.tabSelectedIndices],
                selectionAnchor: this.tabSelectionAnchor,
                insertAtBar: this.tabInsertAtBar,
            };
        },

        restoreTabEditorSnapshot(snapshot) {
            this.tabDraft = snapshot.draft;
            this.tabSelectedIndex = snapshot.selectedIndex;
            this.tabSelectedIndices = snapshot.selectedIndices;
            this.tabInsertAtBar = snapshot.insertAtBar;
            this.tabSelectionAnchor = snapshot.selectionAnchor ?? null;
        },

        recordTabEditorUndo() {
            this.tabUndoHistory = [...this.tabUndoHistory.slice(-49), this.tabEditorSnapshot()];
            this.tabRedoHistory = [];
        },

        undoTabEditorChange() {
            if (!this.tabUndoHistory.length) return;
            this.finishPendingTabDigit(false);
            const last = this.tabUndoHistory.at(-1);
            this.tabRedoHistory = [...this.tabRedoHistory.slice(-49), this.tabEditorSnapshot()];
            this.tabUndoHistory = this.tabUndoHistory.slice(0, -1);
            this.restoreTabEditorSnapshot(last);
        },

        redoTabEditorChange() {
            if (!this.tabRedoHistory.length) return;
            this.finishPendingTabDigit(false);
            const next = this.tabRedoHistory.at(-1);
            this.tabUndoHistory = [...this.tabUndoHistory.slice(-49), this.tabEditorSnapshot()];
            this.tabRedoHistory = this.tabRedoHistory.slice(0, -1);
            this.restoreTabEditorSnapshot(next);
        },

        changeTabEditorDuration(value) {
            const duration = Number(value);
            const selected = this.tabInsertAtBar ? [] : (this.tabSelectedIndices.length
                ? this.tabSelectedIndices
                : (this.tabSelectedIndex < this.tabDraft.beats.length ? [this.tabSelectedIndex] : []));
            if (selected.length) this.recordTabEditorUndo();
            this.tabDraft = {
                ...this.tabDraft,
                duration,
                beats: changeSelectedTabDurations(this.tabDraft.beats, selected, duration),
            };
        },

        setTabEditorRhythm(changes) {
            const selected = this.tabInsertAtBar ? [] : (this.tabSelectedIndices.length
                ? this.tabSelectedIndices
                : (this.tabSelectedIndex < this.tabDraft.beats.length ? [this.tabSelectedIndex] : []));
            if (selected.length) this.recordTabEditorUndo();
            this.tabDraft = {
                ...this.tabDraft,
                ...changes,
                beats: changeSelectedTabRhythm(this.tabDraft.beats, selected, changes),
            };
        },

        addTabEditorRest() {
            const index = this.tabSelectedIndex;
            const inserting = this.tabInsertAtBar || index >= this.tabDraft.beats.length;
            let beat = {
                rest: true,
                duration: this.tabDraft.duration,
                ...(this.tabDraft.dotted ? { dotted: true } : {}),
                ...(this.tabDraft.tuplet === 3 ? { tuplet: 3 } : {}),
            };
            // Any insertion at an incomplete measure boundary must use the
            // remaining musical space. An earlier dotted/triplet selection
            // must not make a sixteenth rest too long or too short.
            if (inserting) {
                const measure = this.tabEditorMeasureLayout().find(item =>
                    item.endIndex + 1 === index && item.used < item.capacity - 1e-7
                );
                if (measure) {
                    const remaining = measure.capacity - measure.used;
                    if (Math.abs(tabBeatUnits(beat) - remaining) > 1e-7 &&
                        (tabBeatUnits(beat) > remaining + 1e-7 ||
                         (index === this.tabDraft.beats.length && remaining <= 1 + 1e-7))) {
                        const duration = [1, 2, 4, 8, 16].find(value =>
                            16 / value <= remaining + 1e-7
                        );
                        if (!duration) {
                            this.showToast?.('No rhythmic space remains for a rest here.', 'error');
                            return;
                        }
                        beat = { rest: true, duration };
                    }
                }
            }
            const beats = [...this.tabDraft.beats];
            if (!inserting) {
                beats[index] = beat;
            } else {
                beats.splice(index, 0, beat);
            }
            // The break calculator must use the actual rhythm of the rest,
            // not modifiers inherited from a previously selected note.
            const insertionDraft = { ...this.tabDraft, dotted: Boolean(beat.dotted), tuplet: beat.tuplet ?? null };
            const breaks = inserting
                ? breaksForInsertedTabNote(insertionDraft, index, beat.duration,
                    this.stepForm.time_signature_numerator, this.stepForm.time_signature_denominator)
                : this.tabDraft.breaks;
            this.recordTabEditorUndo();
            this.tabDraft = {
                ...this.tabDraft,
                duration: beat.duration,
                dotted: Boolean(beat.dotted),
                tuplet: beat.tuplet ?? null,
                beats: normalizeTabTies(normalizeTabLegato(beats)),
                breaks,
            };
            this.advanceTabEditorPosition(index, false);
        },

        addTabEditorNote(deferAdvance = false) {
            try {
                const next = appendTabNote(this.tabDraft);
                const beats = [...this.tabDraft.beats];
                // Typing on an occupied string edits that fret, never inserts a beat.
                const occupiedBeat = beats[this.tabSelectedIndex];
                if (!this.tabInsertAtBar && occupiedBeat && !occupiedBeat.rest &&
                    tabBeatNotes(occupiedBeat).some(note => note.string === this.tabDraft.string)) {
                    this.recordTabEditorUndo();
                    beats[this.tabSelectedIndex] = setTabBeatNote(occupiedBeat, this.tabDraft.fret, this.tabDraft.string);
                    this.tabDraft = { ...this.tabDraft, beats: normalizeTabTies(normalizeTabLegato(beats)) };
                    this.tabSelectedIndices = [this.tabSelectedIndex];
                    this.tabSelectionAnchor = this.tabSelectedIndex;
                    return;
                }
                // Empty strings at an existing beat add a simultaneous chord note.
                const current = beats[this.tabSelectedIndex];
                const isChordEntry = current && !current.rest && !this.tabInsertAtBar &&
                    !tabBeatNotes(current).some(note => note.string === this.tabDraft.string);
                if (isChordEntry) {
                    this.recordTabEditorUndo();
                    beats[this.tabSelectedIndex] = setTabBeatNote(current, this.tabDraft.fret, this.tabDraft.string);
                    this.tabDraft = { ...this.tabDraft, beats: normalizeTabTies(normalizeTabLegato(beats)) };
                    this.tabSelectedIndices = [this.tabSelectedIndex];
                    this.tabSelectionAnchor = this.tabSelectedIndex;
                    return;
                }
                // Reuse a rest; otherwise insert at the caret without overwriting a note.
                this.recordTabEditorUndo();
                if (beats[this.tabSelectedIndex]?.rest && !this.tabInsertAtBar) {
                    beats[this.tabSelectedIndex] = next.beats.at(-1);
                } else {
                    beats.splice(this.tabSelectedIndex, 0, next.beats.at(-1));
                }
                this.tabDraft = { ...next, beats: normalizeTabTies(normalizeTabLegato(beats)), breaks: beats.length > this.tabDraft.beats.length ? breaksForInsertedTabNote(this.tabDraft, this.tabSelectedIndex, next.beats.at(-1).duration, this.stepForm.time_signature_numerator, this.stepForm.time_signature_denominator) : this.tabDraft.breaks };
                if (!deferAdvance) this.advanceTabEditorPosition(this.tabSelectedIndex, false);
                else this.tabInsertAtBar = false;
            } catch (error) {
                this.showToast?.(error.message, "error");
            }
        },

        clearTabEditorNote() {
            this.finishPendingTabDigit(false);
            if (this.tabInsertAtBar) return;
            const indices = this.tabSelectedIndices.length
                ? this.tabSelectedIndices
                : [this.tabSelectedIndex];
            const selected = new Set(indices.filter(index => index >= 0 && index < this.tabDraft.beats.length));
            if (!selected.size) return;
            // A multi-selection clears whole beats, regardless of which
            // string was active when the range was selected. A single
            // selection keeps the existing per-string Backspace behavior.
            const beats = this.tabSelectedIndices.length > 1
                ? clearTabNotes(this.tabDraft.beats, [...selected])
                : this.tabDraft.beats.map((beat, index) =>
                    selected.has(index) ? removeTabNoteAtString(beat, this.tabDraft.string) : beat);
            if (beats.every((beat, index) => beat === this.tabDraft.beats[index])) return;
            this.recordTabEditorUndo();
            this.tabDraft = { ...this.tabDraft, beats: normalizeTabTies(normalizeTabLegato(beats)) };
        },

        // Delete removes selected beat positions; Backspace clears the current
        // string in all selected beats, preserving their rhythmic space.
        undoTabEditorNote() {
            this.finishPendingTabDigit(false);
            const length = this.tabDraft.beats.length;
            const indices = this.tabSelectedIndices.length
                ? this.tabSelectedIndices
                : [Math.min(this.tabSelectedIndex, length - 1)];
            const selected = [...new Set(indices.filter(index => index >= 0 && index < length))]
                .sort((a, b) => b - a);
            if (!selected.length) return;
            this.recordTabEditorUndo();
            const beats = [...this.tabDraft.beats];
            let breaks = [...(this.tabDraft.breaks ?? [])];
            for (const index of selected) {
                beats.splice(index, 1);
                breaks = updateTabBreaks(breaks, index, 'delete', beats.length);
            }
            if (!beats.length && this.tabScoreView === 'score') beats.push({ rest: true, duration: 1 });
            this.tabDraft = { ...this.tabDraft, beats: normalizeTabTies(normalizeTabLegato(beats)), breaks };
            this.selectTabEditorCell(Math.min(selected.at(-1), beats.length - 1), this.tabDraft.string);
        },

        tabClipboard: [],

        copyTabEditorSelection(cut = false) {
            this.finishPendingTabDigit(false);
            if (this.tabInsertAtBar) return;
            const indices = this.tabSelectedIndices.length
                ? this.tabSelectedIndices : [this.tabSelectedIndex];
            const fragment = copyTabFragment(this.tabDraft, indices,
                this.stepForm.time_signature_numerator, this.stepForm.time_signature_denominator);
            if (!fragment.length) return;
            this.tabClipboard = fragment;
            if (cut) this.undoTabEditorNote();
        },

        pasteTabEditorSelection() {
            if (!this.tabClipboard.length) return;
            this.finishPendingTabDigit(false);
            try {
                const result = pasteTabFragment(this.tabDraft, this.tabSelectedIndex, this.tabClipboard,
                    this.stepForm.time_signature_numerator, this.stepForm.time_signature_denominator,
                    this.tabInsertAtBar);
                if (!result) return;
                this.recordTabEditorUndo();
                this.tabDraft = result.draft;
                this.selectTabEditorCell(result.start, this.tabDraft.string);
                this.tabSelectedIndices = Array.from({ length: result.count }, (_, i) => result.start + i);
                this.tabSelectedIndex = result.start + result.count - 1;
            } catch (error) {
                this.showToast?.(error.message, 'error');
            }
        },

        handleTabEditorKey(event) {
            const tag = event.target?.tagName;
            // Keep native text-field undo, but allow score undo even when a
            // toolbar button has keyboard focus.
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) return;
            if (event.ctrlKey || event.metaKey) {
                const key = event.key.toLowerCase();
                if (!this.tabFullMusicXml &&
                    !event.altKey && !event.shiftKey && ['c', 'x', 'v'].includes(key) &&
                    tag !== 'BUTTON' && !event.target?.isContentEditable) {
                    event.preventDefault();
                    if (event.repeat || this.prototypePlaying || this.prototypePlaybackLoading) return;
                    if (key === 'v') this.pasteTabEditorSelection();
                    else this.copyTabEditorSelection(key === 'x');
                    return;
                }
                if (key === 'z' || (key === 'y' && !event.shiftKey)) {
                    event.preventDefault();
                    if (event.shiftKey || key === 'y') this.redoTabEditorChange();
                    else this.undoTabEditorChange();
                    return;
                }
            }
            if (tag === 'BUTTON') return;
            const techniqueKey = event.key.toLowerCase();
            if (!event.ctrlKey && !event.metaKey &&
                !event.altKey && !event.repeat && techniqueKey === 'i') {
                event.preventDefault();
                this.prepareTabEditorScoreInsertion();
                return;
            }
            if (!event.ctrlKey && !event.metaKey && !event.altKey && !event.repeat &&
                techniqueKey === 'r') {
                event.preventDefault();
                this.addTabEditorRest();
                return;
            }
            if (!event.ctrlKey && !event.metaKey && !event.altKey && !event.repeat &&
                techniqueKey === 'l') {
                event.preventDefault();
                this.toggleTabEditorTie();
                return;
            }
            if (!event.ctrlKey && !event.metaKey && !event.altKey && !event.repeat &&
                techniqueKey === 'm') {
                event.preventDefault();
                this.toggleTabEditorPalmMute();
                return;
            }
            if (!event.ctrlKey && !event.metaKey && !event.altKey && !event.repeat &&
                (techniqueKey === 'h' || techniqueKey === 'p')) {
                event.preventDefault();
                this.toggleTabEditorLegato(techniqueKey.toUpperCase());
                return;
            }
            if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
                event.preventDefault();
                const delta = event.key === 'ArrowUp' ? -1 : 1;
                const string = Math.max(1, Math.min(6, this.tabDraft.string + delta));
                if (this.tabInsertAtBar) {
                    this.tabDraft = { ...this.tabDraft, string };
                } else {
                    this.selectTabEditorCell(this.tabSelectedIndex, string);
                }
            } else if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                const delta = event.key === 'ArrowLeft' ? -1 : 1;
                if (event.shiftKey && (event.ctrlKey || event.metaKey) && !event.altKey) {
                    this.finishPendingTabDigit(false);
                    const measures = this.tabEditorMeasureLayout();
                    const current = this.tabEditorActiveMeasureIndex(measures);
                    const target = measures[current + delta]?.notes[0];
                    if (current >= 0 && target) this.selectTabEditorCell(target.index, this.tabDraft.string);
                    return;
                }
                // Shift+Arrow selects existing beats only. The extra caret
                // position after the final beat must not clear the selection
                // when key repeat continues at the end of the score.
                const lastIndex = this.tabDraft.beats.length - 1;
                if (delta === 1 && !event.shiftKey) {
                    this.finishPendingTabDigit(false);
                    if (this.tabInsertAtBar) {
                        if (this.tabSelectedIndex <= lastIndex) {
                            this.selectTabEditorCell(this.tabSelectedIndex, this.tabDraft.string);
                        } else {
                            this.appendTabEditorMeasure();
                        }
                    } else if (this.tabSelectedIndex > lastIndex) {
                        this.appendTabEditorMeasure();
                    } else {
                        this.advanceTabEditorPosition(this.tabSelectedIndex);
                    }
                    return;
                }
                if (event.shiftKey && lastIndex < 0) return;
                const maxIndex = event.shiftKey ? lastIndex : this.tabDraft.beats.length;
                const index = Math.max(0, Math.min(maxIndex, this.tabSelectedIndex + delta));
                if (event.shiftKey && index === this.tabSelectedIndex) return;
                this.selectTabEditorCell(index, this.tabDraft.beats[index]?.string ?? this.tabDraft.string, event.shiftKey ? { shiftKey: true } : null);
            } else if (event.key === '+' || event.key === '=' || event.key === '-') {
                event.preventDefault();
                const current = TAB_DURATIONS.indexOf(Number(this.tabDraft.duration));
                if (current !== -1) {
                    const next = Math.max(0, Math.min(TAB_DURATIONS.length - 1,
                        current + (event.key === '-' ? 1 : -1)));
                    this.changeTabEditorDuration(TAB_DURATIONS[next]);
                }
            } else if (/^[0-9]$/.test(event.key)) {
                event.preventDefault();
                this.enterTabFretDigit(Number(event.key));
            } else if (event.key === 'Delete') {
                event.preventDefault();
                this.undoTabEditorNote();
            } else if (event.key === 'Backspace') {
                event.preventDefault();
                this.clearTabEditorNote();
            }
        },

        async saveNewTabExercise(closeAfterSave = true) {
            if (!this.canCreateTablatures) return;
            if (this.tabEditorSaving || (this.tabEditorEditingIndex === null && this.steps.length >= this.maxSteps)) return;
            let alphaTex;
            try {
                alphaTex = this.tabFullMusicXml ? null : serializeTabDraft(
                    this.tabDraft,
                    this.stepForm.time_signature_numerator,
                    this.stepForm.bpm,
                    this.stepForm.time_signature_denominator,
                );
            } catch (error) {
                this.showToast?.(error.message, "error");
                return;
            }
            this.tabEditorSaving = true;
            try {
                const payload = { ...this.getStepFormPayload(), alpha_tex: alphaTex, musicxml_source: this.tabFullMusicXml };
                if (this.tabEditorEditingIndex !== null) {
                    const index = this.tabEditorEditingIndex;
                    const existing = this.steps[index];
                    if (!existing || existing.tab_source !== "user") return;
                    let updated;
                    if (this.usesServerPersistence) {
                        updated = await this.updateRoutineStep(existing.id, payload);
                        if (!updated) return;
                    } else {
                        updated = { ...existing, ...payload, tab_source: "user" };
                    }
                    this.steps.splice(index, 1, updated);
                    if (!this.usesServerPersistence) this.saveToLocalStorage();
                    // alphaTab keeps a mounted API instance, so changing Alpine's
                    // data-alpha-tex binding alone does not replace its score.
                    this.$nextTick(() => {
                        const element = this.$root.querySelector(
                            `[data-alphatab][data-exercise-index="${index}"]`
                        );
                        if (!element) return;
                        window.dispatchEvent(new CustomEvent("alphatab:update", {
                            detail: {
                                element,
                                alphaTex: updated.alpha_tex,
                                bpm: Number(updated.bpm),
                                instrument: this.tabDraft.instrument,
                            },
                        }));
                    });
                    if (closeAfterSave) {
                        
                        this.closeNewTabEditor();
                        this.resetStepForm();
                        this.showToast?.('Tablature saved', 'success');
                    } else {
                        this.showToast?.('Tablature saved', 'success');
                    }
                    return;
                }
                let createdStep;
                if (this.usesServerPersistence) {
                    createdStep = await this.storeRoutineStep(payload);
                    if (!createdStep) return;
                } else {
                    createdStep = {
                        ...payload,
                        position: this.steps.length,
                        origin: "custom",
                        tab_source: "user",
                    };
                }
                this.steps.push(createdStep);
                if (!closeAfterSave) this.tabEditorEditingIndex = this.steps.length - 1;
                if (!this.usesServerPersistence) this.saveToLocalStorage();
                this.track("exercise_created", {
                    exercise_index: this.steps.length - 1,
                    exercise_origin: "custom",
                    has_tablature: true,
                });
                if (closeAfterSave) {
                    
                    this.closeNewTabEditor();
                    this.resetStepForm();
                    this.showToast?.('Tablature saved', 'success');
                } else {
                    this.showToast?.('Tablature saved', 'success');
                }
                this.$nextTick(() => window.dispatchEvent(new Event("picker:sync")));
            } finally {
                this.tabEditorSaving = false;
            }
        },

    };
}
