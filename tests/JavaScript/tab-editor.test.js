import test from 'node:test'
import assert from 'node:assert/strict'
import { createTabDraft, appendTabNote, serializeTabDraft, layoutTabMeasures, parseTabDraft, selectTabNoteIndices, changeSelectedTabDurations } from '../../resources/js/metronome/alphatab/tab-editor.js'

test('adds notes and serializes a four-beat measure', () => {
    let draft = createTabDraft()
    for (const fret of [5, 7, 8, 10, 12, 10, 8, 7, 5]) {
        draft = appendTabNote(draft, fret, 1)
    }
    const tex = serializeTabDraft(draft, 4, 120)
    assert.match(tex, /\\tempo 120/)
    assert.match(tex, /\\ts 4 4/)
    assert.match(tex, /5\.1/)
    assert.match(tex, /\|/)
    assert.equal(draft.beats.length, 9)
})

test('rejects unsupported strings, frets and empty scores', () => {
    const draft = createTabDraft()
    assert.throws(() => appendTabNote(draft, 25, 1), RangeError)
    assert.throws(() => appendTabNote(draft, 3, 7), RangeError)
    assert.throws(() => serializeTabDraft(draft), RangeError)
})

test('four quarter notes fill exactly one 4/4 measure', () => {
    let draft = { ...createTabDraft(), duration: 4 };
    for (let i = 0; i < 4; i++) draft = appendTabNote(draft, i, 1);
    const measures = layoutTabMeasures(draft, 4);
    assert.equal(measures.length, 1);
    assert.equal(measures[0].complete, true);
    assert.equal(measures[0].endIndex, 3);
    assert.doesNotMatch(serializeTabDraft(draft), /\|/);
    draft = appendTabNote(draft, 4, 1);
    assert.equal(layoutTabMeasures(draft, 4).length, 2);
    assert.match(serializeTabDraft(draft), /\|/);
});

test('mixed durations start a new measure before overflow', () => {
    let draft = createTabDraft();
    draft = appendTabNote({ ...draft, duration: 2 }, 1, 1);
    draft = appendTabNote({ ...draft, duration: 4 }, 2, 1);
    draft = appendTabNote({ ...draft, duration: 2 }, 3, 1);
    const measures = layoutTabMeasures(draft, 4);
    assert.equal(measures.length, 2);
    assert.equal(measures[0].used, 12);
    assert.equal(measures[1].used, 8);
});

test('metadata headers contain one backslash, not two', () => {
    const tex = serializeTabDraft(appendTabNote(createTabDraft(), 0, 1), 4, 126);
    assert.equal(tex.split('\n')[0], '\\tempo 126');
    assert.equal(tex.split('\n')[1], '\\ts 4 4');
    assert.equal(tex.split('\n')[2], '\\staff {tabs}');
    assert.doesNotMatch(tex, /\\\\tempo/);
});

test('user-authored tab can be parsed for editing and round-trips without losing durations', () => {
    let draft = createTabDraft();
    draft = appendTabNote({ ...draft, duration: 8 }, 0, 1);
    draft = appendTabNote({ ...draft, duration: 4 }, 5, 2);
    draft = appendTabNote({ ...draft, duration: 16 }, 7, 3);
    const alphaTex = serializeTabDraft(draft, 4, 126);
    const parsed = parseTabDraft(alphaTex);
    assert.deepEqual(parsed.draft.beats, draft.beats);
    assert.equal(parsed.bpm, 126);
    assert.equal(parsed.numerator, 4);
    assert.equal(serializeTabDraft(parsed.draft, parsed.numerator, parsed.bpm), alphaTex);
});

test('parser refuses unfamiliar alphaTex rather than losing notes', () => {
    assert.throws(() => parseTabDraft('\\tempo 100\n\\ts 4 4\n\\staff {tabs}\n.\n:4 0.1 (bend)'), Error);
});

test('measure layout identifies incomplete overflow without changing notes', () => {
    let draft = createTabDraft();
    draft = appendTabNote({ ...draft, duration: 2 }, 1, 1);
    draft = appendTabNote({ ...draft, duration: 4 }, 2, 1);
    draft = appendTabNote({ ...draft, duration: 2 }, 3, 1);
    const measures = layoutTabMeasures(draft, 4);
    assert.equal(measures[0].complete, false);
    assert.equal(measures[0].overflowBeforeIndex, 2);
    assert.equal(measures[0].capacity - measures[0].used, 4);
    assert.equal(measures[1].overflowBeforeIndex, null);
    assert.deepEqual(measures.flatMap(m => m.notes.map(n => n.index)), [0, 1, 2]);
});

test('shift range and modifier toggle choose note indices without changing notes', () => {
    assert.deepEqual(selectTabNoteIndices([1], 1, 4, 'range', 7), [1, 2, 3, 4]);
    assert.deepEqual(selectTabNoteIndices([1, 2, 3], 1, 2, 'toggle', 7), [1, 3]);
    assert.deepEqual(selectTabNoteIndices([1, 3], 1, 5, 'toggle', 7), [1, 3, 5]);
    assert.deepEqual(selectTabNoteIndices([1], 1, 8, 'range', 7), [1]);
});
test('duration edit only affects selected notes', () => {
    const notes = [0, 1, 2, 3].map(fret => ({ fret, string: 1, duration: 8 }));
    const edited = changeSelectedTabDurations(notes, [1, 3], 4);
    assert.deepEqual(edited.map(note => note.duration), [8, 4, 8, 4]);
    assert.deepEqual(notes.map(note => note.duration), [8, 8, 8, 8]);
});
