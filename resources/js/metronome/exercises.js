import { createTabDraft, appendTabNote, serializeTabDraft, parseTabDraft, layoutTabMeasures, selectTabNoteIndices, changeSelectedTabDurations, clearTabNotes, shiftTabBreaks, updateTabBreaks, breaksForInsertedTabNote, TAB_STRINGS } from "./alphatab/tab-editor.js";

export function exercises() {
    return {
        mobileExpandedIndex: 0,
        tabEditorOpen: false,
        tabEditorEditingIndex: null,
        tabEditorSaving: false,
        tabPreviewPlaying: false,
        tabDraft: createTabDraft(),
        tabStringNames: TAB_STRINGS,
        tabSelectedIndex: 0,
        tabSelectedIndices: [],
        tabSelectionAnchor: null,
        tabDragAnchor: null,
        tabDragging: false,
        tabDragMoved: false,
        tabDigitPending: null,
        tabDigitTimer: null,
        tabViewportWidth: 700,

        // EXERCISE STATE
        activeExerciseIndex: null,

        // EXERCISE FORM
        stepFormMode: "create",
        stepFormIndex: null,
        isStepFormOpen: false,

        stepForm: {
            name: "",
            bpm: 100,
            mode: "drill",
            duration_seconds: 60,
            time_signature_numerator: 4,
            time_signature_denominator: 4,
        },

        stepFormMinutes: 1,
        stepFormSeconds: 0,

        stepFormInitial: null,
        stepFormOpenedAt: null,

        // PLAYBACK FLOW
        autoAdvance: true,
        isWaitingForNextExercise: false,
        nextExerciseIndex: null,

        // PRACTICE REVIEW
        isPracticeReviewOpen: false,
        practiceFeeling: null,
        practiceFeelingConfirmation: "",

        // MOBILE EXERCISE ACTIONS
        exerciseActionBusy: false,
        deletedExerciseUndo: null,
        deletedExerciseUndoTimer: null,

        clearDeletedExerciseUndo() {
            clearTimeout(this.deletedExerciseUndoTimer);
            this.deletedExerciseUndoTimer = null;
            this.deletedExerciseUndo = null;
        },

        async undoDeletedExercise() {
            const pending = this.deletedExerciseUndo;
            if (!pending || this.exerciseActionBusy) return;
            this.exerciseActionBusy = true;
            try {
                let restored = pending.step;
                if (this.usesServerPersistence) {
                    restored = await this.restoreDeletedRoutineStep(pending.token);
                    if (!restored) return;
                }
                const index = Math.min(pending.index, this.steps.length);
                this.steps.splice(index, 0, restored);
                if (!this.usesServerPersistence) this.saveToLocalStorage();
                if (this.mobileExpandedIndex >= index) this.mobileExpandedIndex++;
                this.clearDeletedExerciseUndo();
            } finally {
                this.exerciseActionBusy = false;
            }
        },
        mobileExerciseKeyCounter: 0,

        mobileExerciseKey(step) {
            const currentKey = step._mobileExerciseKey;
            const currentIndex = this.steps.indexOf(step);

            // Old local routines and duplicated steps can contain repeated
            // keys. Keep the first occurrence and re-key later duplicates.
            const hasEarlierOwner = currentKey && this.steps.some(
                (other, index) =>
                    index < currentIndex &&
                    other._mobileExerciseKey === currentKey
            );

            if (!currentKey || hasEarlierOwner) {
                let nextKey;

                do {
                    nextKey = `local-${++this.mobileExerciseKeyCounter}`;
                } while (
                    this.steps.some(other =>
                        other !== step &&
                        other._mobileExerciseKey === nextKey
                    )
                );

                step._mobileExerciseKey = nextKey;
            }

            return step._mobileExerciseKey;
        },

        async duplicateMobileExercise(index) {
            if (this.exerciseActionBusy || this.isPlaying || this.isWaitingForNextExercise || this.steps.length >= this.maxSteps) return;
            const source = this.steps[index];
            if (!source) return;

            this.exerciseActionBusy = true;
            try {
                let copy;
                if (this.usesServerPersistence) {
                    copy = await this.performRoutineStepAction(source.id, 'duplicate', 'POST');
                } else {
                    copy = { ...source, name: source.name + ' (copy)', id: undefined, origin: 'custom' };
                }
                if (!copy) return;
                this.steps.splice(index + 1, 0, copy);
                if (!this.usesServerPersistence) this.saveToLocalStorage();
                if (this.mobileExpandedIndex > index) this.mobileExpandedIndex++;
            } finally {
                this.exerciseActionBusy = false;
            }
        },

        async moveMobileExercise(index, direction) {
            if (this.exerciseActionBusy || this.isPlaying || this.isWaitingForNextExercise) return;
            const target = index + direction;
            if (target < 0 || target >= this.steps.length) return;
            const step = this.steps[index];
            this.exerciseActionBusy = true;
            try {
                if (this.usesServerPersistence) {
                    const result = await this.performRoutineStepAction(step.id, 'move', 'PATCH', { direction: direction < 0 ? 'up' : 'down' });
                    if (!result) return;
                }
                this.steps.splice(index, 1);
                this.steps.splice(target, 0, step);
                if (!this.usesServerPersistence) this.saveToLocalStorage();
                if (this.mobileExpandedIndex === index) this.mobileExpandedIndex = target;
                else if (this.mobileExpandedIndex === target) this.mobileExpandedIndex = index;
            } finally {
                this.exerciseActionBusy = false;
            }
        },

        getExerciseOrigin(step) {
            return step?.origin === "custom" ? "custom" : "preset";
        },

        hasAlphaTabPattern(step) {
            return (
                typeof step?.alpha_tex === "string" &&
                step.alpha_tex.trim() !== ""
            );
        },

        syncActiveExerciseBpm(step, index, bpm) {
            const nextBpm = Number(bpm);

            this.metronome.bpm = nextBpm;

            if (this.hasAlphaTabPattern(step)) {
                this.stopMetronome();

                window.dispatchEvent(
                    new CustomEvent("alphatab:set-bpm", {
                        detail: {
                            index,
                            bpm: nextBpm,
                        },
                    }),
                );

                return;
            }

            this.startMetronome(nextBpm);
        },

        getStepFormPayload() {
            const duration =
                Number(this.stepFormMinutes) * 60 +
                Number(this.stepFormSeconds);

            return {
                name: this.stepForm.name,
                bpm: Number(this.stepForm.bpm),

                time_signature_numerator: Number(
                    this.stepForm.time_signature_numerator ?? 4,
                ),

                time_signature_denominator: 4,

                mode: this.stepForm.mode,

                duration_seconds:
                    this.stepForm.mode === "drill" ? duration : null,
                ...(this.stepForm.alpha_tex !== undefined ? { alpha_tex: this.stepForm.alpha_tex } : {}),
            };
        },

        getChangedStepFields(previous, current) {
            if (!previous) {
                return [];
            }

            return [
                "name",
                "bpm",
                "time_signature_numerator",
                "time_signature_denominator",
                "mode",
                "duration_seconds",
            ].filter((field) => {
                return previous[field] !== current[field];
            });
        },

        async removeStep(index) {
            if (this.steps.length <= 1) {
                return false;
            }

            const step = this.steps[index];

            if (!step) {
                return false;
            }

            let deletion = null;
            if (this.usesServerPersistence) {
                if (!step.id) return false;
                deletion = await this.destroyRoutineStep(step.id);
                if (!deletion?.undo_token) return false;
            }

            this.clearDeletedExerciseUndo();
            this.steps.splice(index, 1);
            this.deletedExerciseUndo = { step: { ...step }, index, token: deletion?.undo_token ?? null };
            this.deletedExerciseUndoTimer = setTimeout(() => this.clearDeletedExerciseUndo(), 8000);
            if (this.mobileExpandedIndex === index) this.mobileExpandedIndex = Math.min(index, this.steps.length - 1);
            else if (this.mobileExpandedIndex > index) this.mobileExpandedIndex--;

            if (index < this.currentIndex) {
                this.currentIndex -= 1;
            }

            if (this.currentIndex > this.steps.length - 1) {
                this.currentIndex = this.steps.length - 1;
            }

            if (!this.usesServerPersistence) {
                this.saveToLocalStorage();
            }

            this.$nextTick(() => {
                window.dispatchEvent(new Event("picker:sync"));
            });

            return true;
        },

        resetStepForm() {
            this.stepFormMode = "create";
            this.stepFormIndex = null;

            this.stepForm = {
                name: `Exercise ${this.steps.length + 1}`,
                bpm: this.metronome.bpm,
                mode: "drill",
                duration_seconds: 60,
                time_signature_numerator: 4,
                time_signature_denominator: 4,
            };

            this.stepFormMinutes = 1;
            this.stepFormSeconds = 0;

            this.stepFormInitial = this.getStepFormPayload();
        },

        openNewTabEditor() {
            if (this.stepFormMode !== "create" || this.steps.length >= this.maxSteps) return;
            this.isStepFormOpen = false;
            this.$refs.stepDialog.close();
            this.tabEditorEditingIndex = null;
            this.tabDraft = createTabDraft();
            this.tabSelectedIndex = 0;
            this.tabSelectedIndices = [];
            this.tabSelectionAnchor = null;
            this.tabEditorOpen = true;
            this.$nextTick(() => {
                this.$refs.tabEditorDialog?.showModal();
                this.updateTabEditorWidth();
            });
        },

        openSavedTabEditor(index, viewerDialog = null) {
            const step = this.steps[index];
            if (!step || step.tab_source !== "user" || !step.alpha_tex) return;
            let parsed;
            try {
                parsed = parseTabDraft(step.alpha_tex);
            } catch (error) {
                this.showToast?.(error.message, "error");
                return;
            }
            this.tabEditorEditingIndex = index;
            this.tabDraft = parsed.draft;
            this.tabSelectedIndex = parsed.draft.beats.length;
            this.tabSelectedIndices = [];
            this.tabSelectionAnchor = null;
            this.stepForm = {
                name: step.name,
                bpm: parsed.bpm,
                time_signature_numerator: parsed.numerator,
                time_signature_denominator: 4,
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
            this.$nextTick(() => {
                this.$refs.tabEditorDialog?.showModal();
                this.updateTabEditorWidth();
            });
        },

        closeNewTabEditor() {
            this.stopTabEditorPreview();
            this.finishPendingTabDigit(false);
            this.$refs.tabEditorDialog?.close();
            this.tabEditorOpen = false;
            this.tabEditorEditingIndex = null;
        },

        previewTabEditor() {
            if (this.tabPreviewPlaying) {
                this.stopTabEditorPreview();
                return;
            }
            let alphaTex;
            try {
                alphaTex = serializeTabDraft(this.tabDraft, this.stepForm.time_signature_numerator, this.stepForm.bpm);
            } catch (error) {
                this.showToast?.(error.message, "error");
                return;
            }
            const element = this.$refs.tabEditorPreview;
            if (!element) return;
            this.tabPreviewPlaying = true;
            window.dispatchEvent(new CustomEvent("alphatab:preview-draft", {
                detail: { element, alphaTex, bpm: Number(this.stepForm.bpm), instrument: this.tabDraft.instrument, play: true },
            }));
        },

        stopTabEditorPreview() {
            const element = this.$refs.tabEditorPreview;
            if (element) window.dispatchEvent(new CustomEvent("alphatab:preview-draft", {
                detail: { element, play: false },
            }));
            this.tabPreviewPlaying = false;
        },

        updateTabEditorWidth() {
            const width = this.$refs.tabEditorScore?.clientWidth;
            if (width) this.tabViewportWidth = width;
        },

        tabEditorSystems() {
            // Each six-string staff is kept together when the layout wraps.
            const columns = Math.max(1, Math.floor((this.tabViewportWidth - 68) / 48));
            const count = this.tabEditorColumns();
            const systems = [];
            for (let start = 0; start < count; start += columns) {
                systems.push(Array.from(
                    { length: Math.min(columns, count - start) },
                    (_, i) => start + i,
                ));
            }
            return systems;
        },

        tabEditorMeasureLayout() {
            return layoutTabMeasures(this.tabDraft, this.stepForm.time_signature_numerator);
        },

        insertTabEditorBar() {
            const at = this.tabSelectedIndex;
            if (at <= 0 || at >= this.tabDraft.beats.length) return;

            // Leave an editable rest before the bar, keeping the selected note
            // in the following measure instead of relocating or overwriting it.
            const previousBreak = Math.max(0, ...(this.tabDraft.breaks ?? []).filter(index => index < at));
            const used = this.tabDraft.beats.slice(previousBreak, at)
                .reduce((total, beat) => total + 16 / beat.duration, 0);
            const remaining = this.stepForm.time_signature_numerator * 4 - used;
            const duration = [1, 2, 4, 8, 16].find(value => 16 / value === remaining)
                ?? Number(this.tabDraft.duration);
            const beats = [...this.tabDraft.beats];
            beats.splice(at, 0, { rest: true, duration });
            const breaks = [...new Set([...shiftTabBreaks(this.tabDraft.breaks ?? [], at, 1), at + 1])]
                .sort((a, b) => a - b);

            this.tabDraft = { ...this.tabDraft, beats, breaks, duration };
            this.selectTabEditorCell(at, this.tabDraft.string);
        },

        tabEditorMeasureEnd(index) {
            return this.tabEditorMeasureLayout().some(measure => measure.endIndex === index);
        },

        tabEditorMeasureNumber(index) {
            const measures = this.tabEditorMeasureLayout();
            return measures.findIndex(measure => measure.notes.some(note => note.index === index)) + 1;
        },

        tabEditorMeasureStatus() {
            const measures = this.tabEditorMeasureLayout();
            if (!measures.length) return 'Measure 1 · empty';
            const invalid = measures.filter(measure => !measure.complete).length;
            return `${measures.length} measure(s) · ${invalid} incomplete or overfull`;
        },

        tabEditorMeasureWarnings() {
            return this.tabEditorMeasureLayout().map((measure, index) => ({
                number: index + 1,
                status: measure.complete ? 'complete' : measure.used > measure.capacity ? 'overflow' : 'incomplete',
                message: measure.complete
                    ? `Measure ${index + 1}: complete`
                    : measure.used > measure.capacity
                        ? `Measure ${index + 1}: ${measure.used - measure.capacity} sixteenth-note units over limit`
                        : `Measure ${index + 1}: ${measure.capacity - measure.used} sixteenth-note units remaining`,
            }));
        },

        tabEditorColumns() {
            return Math.max(1, this.tabDraft.beats.length + 1);
        },

        tabEditorNoteAt(index, string) {
            const note = this.tabDraft.beats[index];
            if (note?.rest) return null;
            return note?.string === string ? note.fret : null;
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
            this.finishPendingTabDigit(false);
            const note = this.tabDraft.beats[index];
            this.tabDraft = {
                ...this.tabDraft,
                string: note?.rest ? string : (note?.string ?? string),
                fret: note?.rest ? 0 : (note?.fret ?? this.tabDraft.fret),
                duration: note?.duration ?? this.tabDraft.duration,
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
                    this.tabDraft = {
                        ...this.tabDraft,
                        beats: this.tabDraft.beats.map((note, index) =>
                            index === pending.index ? { ...note, fret } : note
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
                const entry = { digit, index };
                this.tabDigitPending = entry;
                clearTimeout(this.tabDigitTimer);
                this.tabDigitTimer = setTimeout(() => {
                    if (this.tabDigitPending === entry) this.finishPendingTabDigit();
                }, 650);
            }
        },

        changeTabEditorDuration(value) {
            const duration = Number(value);
            const selected = this.tabSelectedIndices.length
                ? this.tabSelectedIndices
                : (this.tabSelectedIndex < this.tabDraft.beats.length ? [this.tabSelectedIndex] : []);
            this.tabDraft = {
                ...this.tabDraft,
                duration,
                beats: changeSelectedTabDurations(this.tabDraft.beats, selected, duration),
            };
        },

        addTabEditorNote() {
            try {
                const next = appendTabNote(this.tabDraft);
                const beats = [...this.tabDraft.beats];
                // Reuse a rest; otherwise insert at the caret without overwriting a note.
                if (beats[this.tabSelectedIndex]?.rest) {
                    beats[this.tabSelectedIndex] = next.beats.at(-1);
                } else {
                    beats.splice(this.tabSelectedIndex, 0, next.beats.at(-1));
                }
                this.tabDraft = { ...next, beats, breaks: beats.length > this.tabDraft.beats.length ? breaksForInsertedTabNote(this.tabDraft, this.tabSelectedIndex, next.beats.at(-1).duration, this.stepForm.time_signature_numerator) : this.tabDraft.breaks };
                this.tabSelectedIndex = Math.min(beats.length, this.tabSelectedIndex + 1);
                this.tabSelectedIndices = [];
                this.tabSelectionAnchor = null;
            } catch (error) {
                this.showToast?.(error.message, "error");
            }
        },

        clearTabEditorNote() {
            this.finishPendingTabDigit(false);
            const selected = this.tabSelectedIndices.length
                ? this.tabSelectedIndices
                : (this.tabSelectedIndex < this.tabDraft.beats.length ? [this.tabSelectedIndex] : []);
            if (!selected.length) return;
            this.tabDraft = {
                ...this.tabDraft,
                beats: clearTabNotes(this.tabDraft.beats, selected),
            };
        },

        undoTabEditorNote() {
            if (this.tabSelectedIndex < this.tabDraft.beats.length) {
                const beats = this.tabDraft.beats.filter((_, i) => i !== this.tabSelectedIndex);
                this.tabDraft = { ...this.tabDraft, beats, breaks: updateTabBreaks(this.tabDraft.breaks ?? [], this.tabSelectedIndex, 'delete', beats.length) };
                this.tabSelectedIndex = Math.min(this.tabSelectedIndex, beats.length);
                this.tabSelectedIndices = [];
                this.tabSelectionAnchor = null;
            } else {
                this.tabDraft = { ...this.tabDraft, beats: this.tabDraft.beats.slice(0, -1), breaks: (this.tabDraft.breaks ?? []).filter(index => index < this.tabDraft.beats.length - 1) };
                this.tabSelectedIndex = this.tabDraft.beats.length;
                this.tabSelectedIndices = [];
                this.tabSelectionAnchor = null;
            }
        },

        handleTabEditorKey(event) {
            const tag = event.target?.tagName;
            if (['INPUT', 'TEXTAREA', 'SELECT', 'BUTTON'].includes(tag)) return;
            if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
                event.preventDefault();
                const delta = event.key === 'ArrowUp' ? -1 : 1;
                this.selectTabEditorCell(this.tabSelectedIndex, Math.max(1, Math.min(6, this.tabDraft.string + delta)));
            } else if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                const delta = event.key === 'ArrowLeft' ? -1 : 1;
                const index = Math.max(0, Math.min(this.tabDraft.beats.length, this.tabSelectedIndex + delta));
                this.selectTabEditorCell(index, this.tabDraft.beats[index]?.string ?? this.tabDraft.string, event.shiftKey ? { shiftKey: true } : null);
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
            if (this.tabEditorSaving || (this.tabEditorEditingIndex === null && this.steps.length >= this.maxSteps)) return;
            let alphaTex;
            try {
                alphaTex = serializeTabDraft(
                    this.tabDraft,
                    this.stepForm.time_signature_numerator,
                    this.stepForm.bpm,
                );
            } catch (error) {
                this.showToast?.(error.message, "error");
                return;
            }
            this.tabEditorSaving = true;
            try {
                const payload = { ...this.getStepFormPayload(), alpha_tex: alphaTex };
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
                } else {
                    this.showToast?.('Tablature saved', 'success');
                }
                this.$nextTick(() => window.dispatchEvent(new Event("picker:sync")));
            } finally {
                this.tabEditorSaving = false;
            }
        },

        // MODALS
        openAddStepModal() {
            if (this.steps.length >= this.maxSteps) {
                return;
            }

            this.resetStepForm();
            this.openStepFormModal();

            /* TRACKEAR EVENTO */
            this.track("exercise_form_opened", {
                mode: "create",
            });
        },

        openEditStepModal(index) {
            const step = this.steps[index];

            if (!step) {
                return;
            }

            this.stepFormMode = "edit";
            this.stepFormIndex = index;

            this.stepForm = {
                name: step.name,
                bpm: Number(step.bpm),
                time_signature_numerator: Number(
                    step.time_signature_numerator ?? 4,
                ),

                time_signature_denominator: Number(
                    step.time_signature_denominator ?? 4,
                ),
                mode: step.mode,
                duration_seconds: Number(step.duration_seconds ?? 60),
            };

            this.stepFormMinutes = Math.floor(
                this.stepForm.duration_seconds / 60,
            );
            this.stepFormSeconds = this.stepForm.duration_seconds % 60;
            this.stepFormInitial = this.getStepFormPayload();

            this.openStepFormModal();

            this.track("exercise_form_opened", {
                mode: "edit",
                exercise_origin: this.getExerciseOrigin(step),
                exercise_index: index,
            });
        },

        openStepFormModal() {
            this.stepFormOpenedAt = Date.now();

            this.$nextTick(() => {
                this.isStepFormOpen = true;
                this.$refs.stepDialog.showModal();

                requestAnimationFrame(() => {
                    window.dispatchEvent(new Event("picker:sync"));
                });
            });
        },

        closeStepFormModal(trackCancellation = true) {
            if (trackCancellation && this.isStepFormOpen) {
                const currentPayload = this.getStepFormPayload();

                const changedFields = this.getChangedStepFields(
                    this.stepFormInitial,
                    currentPayload,
                );

                const properties = {
                    mode: this.stepFormMode,
                    changed_fields: changedFields,
                    time_open_seconds: this.stepFormOpenedAt
                        ? Math.round(
                              (Date.now() - this.stepFormOpenedAt) / 1000,
                          )
                        : 0,
                };

                if (
                    this.stepFormMode === "edit" &&
                    this.stepFormIndex !== null
                ) {
                    properties.exercise_origin = this.getExerciseOrigin(
                        this.steps[this.stepFormIndex],
                    );

                    properties.exercise_index = this.stepFormIndex;
                }

                if (changedFields.length > 0) {
                    this.track("exercise_edit_abandoned", properties);
                } else {
                    this.track("exercise_form_cancelled", properties);
                }
            }

            this.isStepFormOpen = false;
            this.$refs.stepDialog.close();

            this.stepFormOpenedAt = null;
        },

        openDeleteStepModal(index) {
            if (this.steps.length <= 1) {
                return;
            }

            const step = this.steps[index];

            if (!step) {
                return;
            }

            this.openConfirmModal({
                title: "Delete exercise?",
                message: `"${step.name}" will be permanently removed from this routine.`,
                confirmLabel: "Delete",

                action: () => {
                    return this.removeStep(index);
                },
            });
        },

        async saveStepForm() {
            const payload = this.getStepFormPayload();

            let analyticsEvent = null;
            let analyticsProperties = {};

            if (this.stepFormMode === "edit") {
                if (this.stepFormIndex === null) {
                    return;
                }

                const existingStep = this.steps[this.stepFormIndex];

                if (!existingStep) {
                    return;
                }

                const previousOrigin = this.getExerciseOrigin(existingStep);

                const changedFields = this.getChangedStepFields(
                    this.stepFormInitial,
                    payload,
                );

                let updatedStep;

                if (this.usesServerPersistence) {
                    updatedStep = await this.updateRoutineStep(
                        existingStep.id,
                        payload,
                    );

                    if (!updatedStep) {
                        return;
                    }
                } else {
                    updatedStep = {
                        ...existingStep,
                        ...payload,
                        origin: "custom",
                    };
                }

                this.steps.splice(this.stepFormIndex, 1, updatedStep);

                const isActiveExercise =
                    this.activeExerciseIndex === this.stepFormIndex &&
                    this.isPlaying;

                if (isActiveExercise) {
                    const playbackConfigurationChanged =
                        changedFields.includes("mode") ||
                        changedFields.includes("duration_seconds") ||
                        changedFields.includes("time_signature_numerator") ||
                        changedFields.includes("time_signature_denominator");

                    if (playbackConfigurationChanged) {
                        this.stop("exercise_changed");
                    } else if (changedFields.includes("bpm")) {
                        this.syncActiveExerciseBpm(
                            updatedStep,
                            this.stepFormIndex,
                            payload.bpm,
                        );
                    }
                }

                if (changedFields.length > 0) {
                    analyticsEvent =
                        previousOrigin === "preset"
                            ? "exercise_customized"
                            : "exercise_updated";

                    analyticsProperties = {
                        exercise_index: this.stepFormIndex,
                        previous_origin: previousOrigin,
                        exercise_origin: "custom",
                        changed_fields: changedFields,
                        bpm: payload.bpm,
                        exercise_mode: payload.mode,
                        duration_seconds: payload.duration_seconds,
                    };
                }
            } else {
                if (this.steps.length >= this.maxSteps) {
                    return;
                }

                let createdStep;

                if (this.usesServerPersistence) {
                    createdStep = await this.storeRoutineStep(payload);

                    if (!createdStep) {
                        return;
                    }
                } else {
                    createdStep = {
                        ...payload,
                        position: this.steps.length,
                        origin: "custom",
                    };
                }

                this.steps.push(createdStep);

                analyticsEvent = "exercise_created";

                analyticsProperties = {
                    exercise_index: this.steps.length - 1,
                    exercise_origin: createdStep.origin,
                    bpm: createdStep.bpm,
                    exercise_mode: createdStep.mode,
                    duration_seconds: createdStep.duration_seconds,
                    exercise_count: this.steps.length,
                };
            }

            if (!this.usesServerPersistence) {
                this.saveToLocalStorage();
            }

            if (analyticsEvent) {
                this.track(analyticsEvent, analyticsProperties);
            }

            // false evita que guardar sea registrado
            // también como una cancelación.
            this.closeStepFormModal(false);

            this.$nextTick(() => {
                window.dispatchEvent(new Event("picker:sync"));
            });

            this.resetStepForm();
        },

        // EXERCISES
        startExercise(index) {
            const step = this.steps[index];

            if (!step) {
                return;
            }

            // Keep the mobile card in sync with manual and automatic playback.
            this.mobileExpandedIndex = index

            const configuredDuration = step.mode === 'drill' ? Number(step.duration_seconds ?? 60) : null

            /*
             * Esto pertenece al contexto del ejercicio,
             * no al motor general.
             */
            this.currentIndex = index;
            this.activeTab = "exercises";

            this.metronome.time_signature_numerator = Number(
                step.time_signature_numerator ?? 4,
            );

            this.metronome.time_signature_denominator = Number(
                step.time_signature_denominator ?? 4,
            );

            /*
             * Inicio compartido de cualquier reproducción.
             */
            this.startPlaybackSession({
                source: "exercise",
                mode: step.mode,
                bpm: step.bpm,
                duration: configuredDuration,
                exerciseIndex: index,
                useAlphaTab: this.hasAlphaTabPattern(step),
            });

            window.dispatchEvent(
                new CustomEvent("routine:exercise-active", {
                    detail: { index },
                }),
            );

            // Esto es por si hay auto advance y queremos activar tambien alphaTex
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    window.dispatchEvent(
                        new CustomEvent("alphatab:start-exercise", {
                            detail: {
                                index,
                            },
                        }),
                    );
                });
            });

            /*
             * Tracking específico del ejercicio.
             */
            this.beginPlaybackTracking({
                source: "exercise",

                metronome_mode: step.mode,
                bpm: Number(step.bpm),

                configured_duration_seconds: configuredDuration,

                exercise_index: index,
                exercise_origin: this.getExerciseOrigin(step),

                // Compatibilidad temporal
                exercise_mode: step.mode,
            });
        },

        updateExerciseBpm(index, bpm) {
            const step = this.steps[index];

            if (!step) {
                return;
            }

            const nextBpm = Number(bpm);

            this.currentIndex = index;

            // Actualiza inmediatamente la interfaz.
            this.steps[index].bpm = nextBpm;
            this.steps[index].origin = "custom";

            // Guarda el cambio.
            if (this.usesServerPersistence) {
                this.queueRoutineStepUpdate(this.steps[index]);
            } else {
                this.saveToLocalStorage();
            }

            this.trackDebounced(
                `exercise-bpm-${step.id ?? index}`,
                "bpm_changed",
                {
                    source: "exercise",
                    exercise_index: index,
                    exercise_origin: "custom",
                    bpm: nextBpm,
                },
                700,
            );

            if (this.activeExerciseIndex === index && this.isPlaying) {
                this.syncActiveExerciseBpm(step, index, nextBpm);
            } else if (this.hasAlphaTabPattern(step)) {
                window.dispatchEvent(
                    new CustomEvent("alphatab:set-bpm", {
                        detail: {
                            index,
                            bpm: nextBpm,
                            renderTempo: true,
                        },
                    }),
                );
            }
        },

        updateExerciseName(index, name) {
            const step = this.steps[index];

            if (!step) {
                return;
            }

            this.steps[index].name = String(name);
            this.steps[index].origin = "custom";

            if (this.usesServerPersistence) {
                this.queueRoutineStepUpdate(this.steps[index]);
            } else {
                this.saveToLocalStorage();
            }
        },

        formatTime(seconds) {
            const value = Number(seconds ?? 0);

            const minutes = Math.floor(value / 60);
            const remainingSeconds = value % 60;

            return `${minutes}:${String(remainingSeconds).padStart(2, "0")}`;
        },

        getStepTimeLabel(step, index) {
            if (!step) {
                return "";
            }

            if (step.mode === "classic") {
                return "Classic";
            }

            if (this.activeExerciseIndex === index && this.isPlaying) {
                return this.formatTime(this.remaining ?? step.duration_seconds);
            }

            return this.formatTime(step.duration_seconds);
        },

        getActiveExerciseName() {
            if (this.activeExerciseIndex === null) {
                return "";
            }

            return this.steps[this.activeExerciseIndex]?.name ?? "";
        },

        getActiveExerciseTimeLabel() {
            if (this.activeExerciseIndex === null) {
                return "";
            }

            const step = this.steps[this.activeExerciseIndex];

            if (!step) {
                return "";
            }

            return this.getStepTimeLabel(step, this.activeExerciseIndex);
        },

        finishExerciseSession() {
            const completedStep = this.steps[this.activeExerciseIndex];

            if (completedStep) {
                this.track("exercise_completed", {
                    source: "exercise",
                    exercise_index: this.activeExerciseIndex,
                    exercise_origin: this.getExerciseOrigin(completedStep),
                    exercise_mode: completedStep.mode,
                    bpm: Number(completedStep.bpm),
                    duration_seconds:
                        completedStep.mode === "drill"
                            ? Number(completedStep.duration_seconds ?? 60)
                            : null,
                    auto_advance: Boolean(this.autoAdvance),
                });
            }

            if (!this.hasAlphaTabPattern(completedStep)) {
                this.playFinishSound();
            }

            if (!this.autoAdvance) {
                window.dispatchEvent(
                    new Event("routine:exercise-clear-active"),
                );

                this.stop("completed");
                return;
            }

            const nextIndex = this.activeExerciseIndex + 1;

            if (nextIndex >= this.steps.length) {
                window.dispatchEvent(
                    new Event("routine:exercise-clear-active"),
                );

                this.stop();
                this.openPracticeReviewModal();
                return;
            }

            window.dispatchEvent(new CustomEvent("routine:exercise-clear-active", {
                detail: { resumeOnNext: true },
            }));

            this.stop("completed");

            // Show the upcoming exercise while waiting for Continue.
            this.mobileExpandedIndex = nextIndex

            this.nextExerciseIndex = nextIndex
            this.isWaitingForNextExercise = true

            this.$nextTick(() => {
                document.activeElement?.blur();
            });
        },

        continueToNextExercise() {
            if (!this.isWaitingForNextExercise) {
                return;
            }

            if (this.nextExerciseIndex === null) {
                return;
            }

            const index = this.nextExerciseIndex;

            this.isWaitingForNextExercise = false;
            this.nextExerciseIndex = null;

            this.startExercise(index);
        },

        openPracticeReviewModal() {
            this.isWaitingForNextExercise = false;
            this.nextExerciseIndex = null;

            this.practiceFeeling = null;
            this.practiceFeelingConfirmation = "";
            this.isPracticeReviewOpen = true;

            this.$nextTick(() => {
                document.activeElement?.blur();
            });
        },

        selectPracticeFeeling(value) {
            this.practiceFeeling = value;

            const confirmation = {
                estranged: "Some days feel detached. Still counts.",
                sad: "Heavy day. You still showed up.",
                happy: "Good. Keep that energy.",
                optimistic: "Nice. That means something is clicking.",
            };

            this.practiceFeelingConfirmation = confirmation[value] ?? "Logged";
        },

        closePracticeReviewModal() {
            this.isPracticeReviewOpen = false;
        },

        get stepFormMinutesOptions() {
            const maxDuration = Number(this.maxExerciseDurationSeconds);

            const maxMinutes = Math.floor(maxDuration / 60);

            return Array.from(
                {
                    length: maxMinutes + 1,
                },
                (_, index) => index,
            );
        },

        get stepFormSecondsOptions() {
            const maxDuration = Number(this.maxExerciseDurationSeconds);

            const selectedMinutes = Number(this.stepFormMinutes);

            const maxMinutes = Math.floor(maxDuration / 60);

            const remainingSeconds = maxDuration % 60;

            const maxSeconds =
                selectedMinutes >= maxMinutes ? remainingSeconds : 59;

            return Array.from(
                {
                    length: maxSeconds + 1,
                },
                (_, index) => index,
            );
        },

        normalizeStepFormDuration() {
            const maxDuration = Number(this.maxExerciseDurationSeconds);

            const currentDuration =
                Number(this.stepFormMinutes) * 60 +
                Number(this.stepFormSeconds);

            const normalizedDuration = Math.min(currentDuration, maxDuration);

            this.stepFormMinutes = Math.floor(normalizedDuration / 60);

            this.stepFormSeconds = normalizedDuration % 60;
        },

        startRoutineOver() {
            this.stop("restart");

            this.currentIndex = 0;
            this.nextExerciseIndex = null;
            this.isWaitingForNextExercise = false;

            window.dispatchEvent(new Event("routine:exercise-clear-active"));

            this.$nextTick(() => {
                this.startExercise(0);
            });
        },
    };
}
