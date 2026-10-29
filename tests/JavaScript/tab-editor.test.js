import test from 'node:test'
import assert from 'node:assert/strict'
import { createTabDraft, appendTabNote, serializeTabDraft, layoutTabMeasures, parseTabDraft, selectTabNoteIndices, changeSelectedTabDurations, clearTabNotes, updateTabBreaks, serializeTabPlayback, playbackAlphaTex } from '../../resources/js/metronome/alphatab/tab-editor.js'

test('adds notes and serializes a four-beat measure', () => {
    let draft = createTabDraft()
    for (const fret of [5, 7, 8, 10, 12, 10, 8, 7, 5]) {
        draft = appendTabNote(draft, fret, 1)
    }
    const tex = serializeTabDraft(draft, 4, 120)
    assert.match(tex, /\\tempo 120/)
    assert.match(tex, /\\ts 4 4/)
    assert.match(tex, /5\.1/)
    assert.doesNotMatch(tex, /\|/)
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
    assert.equal(layoutTabMeasures(draft, 4).length, 1);
    assert.equal(layoutTabMeasures(draft, 4)[0].used, 20);
});

test('mixed durations start a new measure before overflow', () => {
    let draft = createTabDraft();
    draft = appendTabNote({ ...draft, duration: 2 }, 1, 1);
    draft = appendTabNote({ ...draft, duration: 4 }, 2, 1);
    draft = appendTabNote({ ...draft, duration: 2 }, 3, 1);
    const measures = layoutTabMeasures(draft, 4);
    assert.equal(measures.length, 1);
    assert.equal(measures[0].used, 20);
});

test('metadata headers contain one backslash, not two', () => {
    const tex = serializeTabDraft(appendTabNote(createTabDraft(), 0, 1), 4, 126);
    assert.equal(tex.split('\n')[0], '\\tempo 126');
    assert.equal(tex.split('\n')[1], '\\instrument 25');
    assert.equal(tex.split('\n')[2], '\\ts 4 4');
    assert.equal(tex.split('\n')[3], '\\staff {tabs}');
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
    assert.equal(measures[0].overflowBeforeIndex, null);
    assert.equal(measures[0].used - measures[0].capacity, 4);
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

test('Backspace-style clearing keeps duration and note positions on alphaTex round-trip', () => {
    let draft = { ...createTabDraft(), duration: 4 };
    for (const fret of [5, 7, 8, 10]) draft = appendTabNote(draft, fret, 1);
    const cleared = { ...draft, beats: clearTabNotes(draft.beats, [1]) };
    assert.equal(cleared.beats.length, 4);
    assert.deepEqual(cleared.beats[1], { rest: true, duration: 4 });
    assert.equal(cleared.beats[2].fret, 8);
    const tex = serializeTabDraft(cleared, 4, 120);
    assert.match(tex, /:4 r/);
    assert.equal(layoutTabMeasures(cleared, 4)[0].complete, true);
    assert.deepEqual(parseTabDraft(tex).draft.beats, cleared.beats);
});

test('chosen instrument round-trips and older scores retain Steel guitar', () => {
    const draft = appendTabNote(createTabDraft(), 7, 1);
    for (const instrument of [24, 25, 26, 27]) {
        const saved = serializeTabDraft({ ...draft, instrument }, 4, 125);
        assert.ok(saved.includes('\\instrument ' + instrument));
        const parsed = parseTabDraft(saved);
        assert.equal(parsed.draft.instrument, instrument);
        assert.equal(serializeTabDraft(parsed.draft, 4, 125), saved);
    }
    const legacy = serializeTabDraft(draft, 4, 125).replace('\\instrument 25\n', '');
    assert.equal(parseTabDraft(legacy).draft.instrument, 25);
    assert.throws(() => serializeTabDraft({ ...draft, instrument: 90 }), RangeError);
});

test('incomplete final measure preserves its actual rhythmic length', () => {
    let draft = { ...createTabDraft(), duration: 4 };
    for (const fret of [5, 7, 9]) draft = appendTabNote(draft, fret, 1);
    const measures = layoutTabMeasures(draft, 4);
    assert.equal(measures.length, 1);
    assert.equal(measures[0].complete, false);
    assert.equal(measures[0].used, 12);
    assert.equal(measures[0].capacity, 16);
    const saved = serializeTabDraft(draft, 4, 120);
    assert.ok(saved.endsWith(':4 5.1 :4 7.1 :4 9.1'));
    assert.deepEqual(parseTabDraft(saved).draft.beats, draft.beats);
});

test('overflow boundary preserves incomplete measures and note order', () => {
    let draft = createTabDraft();
    draft = appendTabNote({ ...draft, duration: 2 }, 5, 1);
    draft = appendTabNote({ ...draft, duration: 4 }, 7, 1);
    draft = appendTabNote({ ...draft, duration: 2 }, 9, 1);
    const measures = layoutTabMeasures(draft, 4);
    assert.deepEqual(measures.map(m => m.used), [20]);
    assert.deepEqual(measures.map(m => m.complete), [false]);
    const saved = serializeTabDraft(draft, 4, 120);
    assert.ok(saved.includes(':4 7.1 :2 9.1'));
    assert.deepEqual(parseTabDraft(saved).draft.beats, draft.beats);
});

test('cleared note retains rhythmic space in incomplete measure', () => {
    let draft = { ...createTabDraft(), duration: 4 };
    for (const fret of [5, 7, 9]) draft = appendTabNote(draft, fret, 1);
    draft = { ...draft, beats: clearTabNotes(draft.beats, [1]) };
    const measures = layoutTabMeasures(draft, 4);
    assert.equal(measures[0].used, 12);
    const saved = serializeTabDraft(draft, 4, 120);
    assert.ok(saved.includes(':4 5.1 :4 r :4 9.1'));
    assert.deepEqual(parseTabDraft(saved).draft.beats, draft.beats);
});

test('manual barlines remain in place even with invalid measure durations', () => {
    let draft = { ...createTabDraft(), duration: 4 };
    for (const fret of [1, 2, 3, 4, 5]) draft = appendTabNote(draft, fret, 1);
    draft = { ...draft, beats: draft.beats.map((beat, index) => index === 3 ? { ...beat, duration: 2 } : beat), breaks: [4] };
    const measures = layoutTabMeasures(draft, 4);
    assert.deepEqual(measures.map(m => m.used), [20, 4]);
    const tex = serializeTabDraft(draft, 4, 120);
    assert.match(tex, /:2 4\.1 \| :4 5\.1/);
    assert.deepEqual(parseTabDraft(tex).draft.breaks, [4]);
});

test('an editable rest before a manual bar preserves the following note', () => {
    let draft = { ...createTabDraft(), duration: 4 };
    for (const fret of [1, 2, 3, 4]) draft = appendTabNote(draft, fret, 1);
    draft = { ...draft, beats: draft.beats.map((beat, index) => index === 3 ? { ...beat, duration: 2 } : beat) };
    const beats = [...draft.beats];
    beats.splice(3, 0, { rest: true, duration: 4 });
    draft = { ...draft, beats, breaks: [4] };
    const measures = layoutTabMeasures(draft, 4);
    assert.deepEqual(measures.map(measure => measure.used), [16, 8]);
    assert.equal(draft.beats[4].fret, 4);
    const saved = serializeTabDraft(draft, 4, 120);
    assert.deepEqual(parseTabDraft(saved).draft.breaks, [4]);
    assert.deepEqual(parseTabDraft(saved).draft.beats, beats);
});

test('overfull bar skips offending notes but resumes normally at next manual bar', () => {
    let draft = { ...createTabDraft(), duration: 4 };
    for (const fret of [1, 2, 3, 4, 5]) draft = appendTabNote(draft, fret, 1);
    draft = { ...draft, beats: draft.beats.map((beat, index) => index === 3 || index === 4 ? { ...beat, duration: 2 } : beat), breaks: [4] };
    const original = serializeTabDraft(draft, 4, 120);
    const playback = playbackAlphaTex(original);
    assert.equal(playbackAlphaTex(original), serializeTabPlayback(draft, 4, 120));
    assert.ok(original.includes(':2 4.1 | :2 5.1'));
    assert.ok(!playback.includes('4.1'));
    assert.ok(playback.includes(':4 r | :2 5.1'));
    assert.equal(layoutTabMeasures(parseTabDraft(playback).draft, 4)[0].used, 16);
    assert.equal(parseTabDraft(original).draft.beats[3].fret, 4);
});

test('incomplete bars stay unchanged when no beat exceeds the meter', () => {
    let draft = { ...createTabDraft(), duration: 4 };
    for (const fret of [1, 2, 3]) draft = appendTabNote(draft, fret, 1);
    const original = serializeTabDraft(draft, 4, 120);
    assert.equal(playbackAlphaTex(original), original);
});

test('barline stays before following note on insertion at boundary', () => {
    assert.deepEqual(updateTabBreaks([3], 3, 'insert', 6), [3]);
    assert.deepEqual(updateTabBreaks([3], 2, 'insert', 6), [4]);
});
test('barlines follow the surviving note and do not remain at the end', () => {
    assert.deepEqual(updateTabBreaks([3], 2, 'delete', 4), [2]);
    assert.deepEqual(updateTabBreaks([3], 3, 'delete', 4), [3]);
    assert.deepEqual(updateTabBreaks([4], 4, 'delete', 4), []);
    assert.deepEqual(updateTabBreaks([2], 0, 'delete', 2), [1]);
});
