import test from 'node:test';
import assert from 'node:assert/strict';
import {
    createTabDraft, toggleTabSlide, normalizeTabLegato, toggleTabPickStroke,
    serializeTabDraft, parseTabDraft, copyTabFragment, pasteTabFragment,
    changeSelectedTabDurations, setTabBeatNote
} from '../../resources/js/metronome/alphatab/tab-editor.js';
import { tablatureEditorMethods } from '../../resources/js/metronome/tablature-editor-controller.js';

const note = (fret, string = 1) => ({ fret, string, duration: 8 });
const draft = beats => ({ ...createTabDraft(), beats });
const roundTrip = beats => parseTabDraft(serializeTabDraft(draft(beats))).draft.beats;

for (const slide of ['ss', 'sl', 'sib', 'sia', 'sou', 'sod']) {
    test('slide ' + slide + ' survives AlphaTex round-trip and toggles off', () => {
        const beats = toggleTabSlide([note(5), note(7)], 0, slide);
        assert.equal(roundTrip(beats)[0].slide, slide);
        assert.equal(toggleTabSlide(beats, 0, slide)[0].slide, undefined);
    });
}

test('connected slides reject different strings, equal frets and missing targets', () => {
    for (const slide of ['ss', 'sl']) {
        for (const beats of [[note(5), note(7, 2)], [note(5), note(5)], [note(5)]]) {
            assert.throws(() => toggleTabSlide(beats, 0, slide), RangeError);
        }
    }
});

test('removing a slide destination clears the orphaned connection', () => {
    const beats = toggleTabSlide([note(5), note(7)], 0, 'ss');
    assert.equal(normalizeTabLegato(beats.slice(0, 1))[0].slide, undefined);
});

test('clipboard preserves complete slide connections but not orphaned links', () => {
    const source = draft(toggleTabSlide([note(5), note(7)], 0, 'sl'));
    const fragment = copyTabFragment(source, [0, 1]);
    const pasted = pasteTabFragment(draft([note(3)]), 0, fragment);
    assert.equal(pasted.draft.beats[pasted.start].slide, 'sl');
    assert.equal(copyTabFragment(source, [0])[0].beats[0].slide, undefined);
});

test('picking directions survive AlphaTex with rhythm modifiers and palm mute', () => {
    const beats = [{ ...note(5), dotted: true, palmMute: true, pickStroke: 'sd' },
        { ...note(7), tuplet: 3, pickStroke: 'su' }];
    assert.deepEqual(roundTrip(beats), beats);
});

test('picking applies to selected notes and chords, skips rests, and replaces direction', () => {
    const beats = [note(5), { notes: [{ fret: 5, string: 1 }, { fret: 7, string: 2 }], duration: 8 }, { rest: true, duration: 8 }];
    const down = toggleTabPickStroke(beats, [0, 1, 2], 'sd');
    assert.deepEqual(down.map(b => b.pickStroke), ['sd', 'sd', undefined]);
    const up = toggleTabPickStroke(down, [0, 1], 'su');
    assert.deepEqual(roundTrip(up).map(b => b.pickStroke), ['su', 'su', undefined]);
    assert.deepEqual(toggleTabPickStroke(up, [0, 1], 'su'), beats);
    assert.equal(toggleTabPickStroke(beats, [2], 'sd'), beats);
});

test('picking survives duration changes, fret edits and clipboard', () => {
    const beats = toggleTabPickStroke([note(5)], [0], 'sd');
    const changed = changeSelectedTabDurations(beats, [0], 16);
    assert.equal(changed[0].pickStroke, 'sd');
    assert.equal(setTabBeatNote(changed[0], 7, 1).pickStroke, 'sd');
    const fragment = copyTabFragment(draft(changed), [0]);
    const pasted = pasteTabFragment(draft([note(3)]), 0, fragment);
    assert.equal(pasted.draft.beats[pasted.start].pickStroke, 'sd');
});

test('editor undo and redo restore slides and picking independently', () => {
    const editor = {
        ...tablatureEditorMethods(), tabDraft: draft([note(5), note(7)]),
        tabSelectedIndex: 0, tabSelectedIndices: [0], tabSelectionAnchor: 0,
        tabInsertAtBar: false, tabUndoHistory: [], tabRedoHistory: []
    };
    editor.toggleTabEditorSlide('ss');
    editor.toggleTabEditorPickStroke('sd');
    assert.equal(editor.tabDraft.beats[0].slide, 'ss');
    assert.equal(editor.tabDraft.beats[0].pickStroke, 'sd');
    editor.undoTabEditorChange();
    assert.equal(editor.tabDraft.beats[0].pickStroke, undefined);
    editor.undoTabEditorChange();
    assert.equal(editor.tabDraft.beats[0].slide, undefined);
    editor.redoTabEditorChange();
    editor.redoTabEditorChange();
    assert.equal(editor.tabDraft.beats[0].slide, 'ss');
    assert.equal(editor.tabDraft.beats[0].pickStroke, 'sd');
});
