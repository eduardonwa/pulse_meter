import {
    createTabDraft, appendTabNote, serializeTabDraft, parseTabDraft,
    layoutTabMeasures, selectTabNoteIndices, changeSelectedTabDurations,
    changeSelectedTabRhythm, tabBeatUnits, clearTabNotes, updateTabBreaks,
    breaksForInsertedTabNote, tabBeatNotes, setTabBeatNote,
    removeTabNoteAtString, TAB_DURATIONS
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
                this.updateTabEditorWidth();
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
                this.updateTabEditorWidth();
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

        updateTabEditorWidth() {
            const width = this.$refs.tabEditorScore?.clientWidth;
            if (width) this.tabViewportWidth = width;
        },

        tabEditorDisplayColumns() {
            // Compute measure boundaries once per layout, not once per string cell.
            const measures = this.tabEditorMeasureLayout();
            const insertions = new Set(measures
                .filter(measure => measure.used < measure.capacity - 1e-7)
                .map(measure => measure.endIndex + 1));
            const measureEnds = new Set(measures
                .filter(measure => measure.used >= measure.capacity - 1e-7)
                .map(measure => measure.endIndex));
            const columns = [];
            for (let index = 0; index <= this.tabDraft.beats.length; index++) {
                if (insertions.has(index)) {
                    columns.push({ index, insertion: true, measureEnd: true });
                }
                // An incomplete final measure has one insertion slot, not
                // a second identical empty trailing column.
                if (index !== this.tabDraft.beats.length || !insertions.has(index)) {
                    columns.push({ index, insertion: false, measureEnd: measureEnds.has(index) });
                }
            }
            return columns;
        },

        tabEditorSystems() {
            const columns = Math.max(1, Math.floor((this.tabViewportWidth - 68) / 48));
            const display = this.tabEditorDisplayColumns();
            const systems = [];
            for (let start = 0; start < display.length; start += columns) {
                systems.push(display.slice(start, start + columns));
            }
            return systems;
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

        tabEditorNoteAt(index, string) {
            const note = this.tabDraft.beats[index];
            if (note?.rest) return null;
            return tabBeatNotes(note).find(n => n.string === string)?.fret ?? null;
        },

        selectTabEditorCell(index, string, event = null) {
            const validNote = index < this.tabDraft.beats.length;
            if (event?.shiftKey && validNote) {
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
            this.tabDraft = {
                ...this.tabDraft,
                string,
                fret: tabBeatNotes(note).find(n => n.string === string)?.fret ?? 0,
                duration: note?.duration ?? this.tabDraft.duration,
                dotted: note ? Boolean(note.dotted) : Boolean(this.tabDraft.dotted),
                tuplet: note ? (note.tuplet ?? null) : this.tabDraft.tuplet,
            };
        },

        startTabDrag(index, string, event) {
            if (event.button !== 0 || event.shiftKey || event.ctrlKey || event.metaKey) return;
            this.selectTabEditorCell(index, string);
            this.tabDragAnchor = index;
            this.tabDragging = true;
            this.tabDragMoved = false;
        },

        extendTabDrag(index) {
            if (!this.tabDragging || this.tabDragAnchor === null || index >= this.tabDraft.beats.length) return;
            if (index === this.tabDragAnchor) return;
            this.tabDragMoved = true;
            this.selectTabEditorCell(index, this.tabDraft.beats[index].string, {
                shiftKey: true,
            });
        },

        endTabDrag() {
            this.tabDragging = false;
            this.tabDragAnchor = null;
        },

        clickTabCell(index, string, event) {
            if (this.tabDragMoved) {
                this.tabDragMoved = false;
                return;
            }
            this.selectTabEditorCell(index, string, event);
        },

        finishPendingTabDigit(advance = true) {
            const pending = this.tabDigitPending;
            clearTimeout(this.tabDigitTimer);
            this.tabDigitTimer = null;
            this.tabDigitPending = null;
            if (advance && pending && this.tabSelectedIndex === pending.index) {
                this.tabSelectedIndex = Math.min(this.tabDraft.beats.length, pending.index + 1);
                this.tabSelectedIndices = [];
                this.tabSelectionAnchor = null;
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
                        beats: this.tabDraft.beats.map((note, index) =>
                            index === pending.index ? setTabBeatNote(note, fret, pending.string) : note
                        ),
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
            this.addTabEditorNote();
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
                insertAtBar: this.tabInsertAtBar,
            };
        },

        restoreTabEditorSnapshot(snapshot) {
            this.tabDraft = snapshot.draft;
            this.tabSelectedIndex = snapshot.selectedIndex;
            this.tabSelectedIndices = snapshot.selectedIndices;
            this.tabInsertAtBar = snapshot.insertAtBar;
            this.tabSelectionAnchor = snapshot.selectedIndices.at(-1) ?? null;
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
            const selected = this.tabSelectedIndices.length
                ? this.tabSelectedIndices
                : (this.tabSelectedIndex < this.tabDraft.beats.length ? [this.tabSelectedIndex] : []);
            if (selected.length) this.recordTabEditorUndo();
            this.tabDraft = {
                ...this.tabDraft,
                duration,
                beats: changeSelectedTabDurations(this.tabDraft.beats, selected, duration),
            };
        },

        setTabEditorRhythm(changes) {
            const selected = this.tabSelectedIndices.length
                ? this.tabSelectedIndices
                : (this.tabSelectedIndex < this.tabDraft.beats.length ? [this.tabSelectedIndex] : []);
            if (selected.length) this.recordTabEditorUndo();
            this.tabDraft = {
                ...this.tabDraft,
                ...changes,
                beats: changeSelectedTabRhythm(this.tabDraft.beats, selected, changes),
            };
        },

        addTabEditorRest() {
            const beat = {
                rest: true,
                duration: this.tabDraft.duration,
                ...(this.tabDraft.dotted ? { dotted: true } : {}),
                ...(this.tabDraft.tuplet === 3 ? { tuplet: 3 } : {}),
            };
            const index = this.tabSelectedIndex;
            const beats = [...this.tabDraft.beats];
            if (index < beats.length && !this.tabInsertAtBar) {
                beats[index] = beat;
            } else {
                beats.splice(index, 0, beat);
            }
            const breaks = beats.length > this.tabDraft.beats.length
                ? breaksForInsertedTabNote(this.tabDraft, index, beat.duration, this.stepForm.time_signature_numerator, this.stepForm.time_signature_denominator)
                : this.tabDraft.breaks;
            this.recordTabEditorUndo();
            this.tabDraft = { ...this.tabDraft, beats, breaks };
            this.tabSelectedIndex = Math.min(beats.length, index + 1);
            this.tabInsertAtBar = false;
            this.tabSelectedIndices = [];
            this.tabSelectionAnchor = null;
        },

        addTabEditorNote() {
            try {
                const next = appendTabNote(this.tabDraft);
                const beats = [...this.tabDraft.beats];
                // Typing on an occupied string edits that fret, never inserts a beat.
                const occupiedBeat = beats[this.tabSelectedIndex];
                if (!this.tabInsertAtBar && occupiedBeat && !occupiedBeat.rest &&
                    tabBeatNotes(occupiedBeat).some(note => note.string === this.tabDraft.string)) {
                    this.recordTabEditorUndo();
                    beats[this.tabSelectedIndex] = setTabBeatNote(occupiedBeat, this.tabDraft.fret, this.tabDraft.string);
                    this.tabDraft = { ...this.tabDraft, beats };
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
                    this.tabDraft = { ...this.tabDraft, beats };
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
                this.tabDraft = { ...next, beats, breaks: beats.length > this.tabDraft.beats.length ? breaksForInsertedTabNote(this.tabDraft, this.tabSelectedIndex, next.beats.at(-1).duration, this.stepForm.time_signature_numerator, this.stepForm.time_signature_denominator) : this.tabDraft.breaks };
                this.tabSelectedIndex = Math.min(beats.length, this.tabSelectedIndex + 1);
                this.tabInsertAtBar = false;
                this.tabSelectedIndices = [];
                this.tabSelectionAnchor = null;
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
            this.tabDraft = { ...this.tabDraft, beats };
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
            this.tabDraft = { ...this.tabDraft, beats, breaks };
            this.tabSelectedIndex = Math.min(selected.at(-1), beats.length);
            this.tabInsertAtBar = false;
            this.tabSelectedIndices = [];
            this.tabSelectionAnchor = null;
        },

        handleTabEditorKey(event) {
            const tag = event.target?.tagName;
            // Keep native text-field undo, but allow score undo even when a
            // toolbar button has keyboard focus.
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) return;
            if (event.ctrlKey || event.metaKey) {
                const key = event.key.toLowerCase();
                if (key === 'z' || (key === 'y' && !event.shiftKey)) {
                    event.preventDefault();
                    if (event.shiftKey || key === 'y') this.redoTabEditorChange();
                    else this.undoTabEditorChange();
                    return;
                }
            }
            // Score cells are buttons for accessibility, but they must still
            // accept Delete, Backspace and the existing tab keyboard controls.
            if (tag === 'BUTTON' && !event.target?.classList?.contains('tab-editor__cell')) return;
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
                // Shift+Arrow selects existing beats only. The extra caret
                // position after the final beat must not clear the selection
                // when key repeat continues at the end of the score.
                const lastIndex = this.tabDraft.beats.length - 1;
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
