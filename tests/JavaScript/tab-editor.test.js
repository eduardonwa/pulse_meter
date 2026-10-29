import test from 'node:test'
import assert from 'node:assert/strict'
import { createTabDraft, appendTabNote, serializeTabDraft, layoutTabMeasures } from '../../resources/js/metronome/alphatab/tab-editor.js'

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
    assert.doesNotMatch(serializeTabDraft(draft), /\\|/);
    draft = appendTabNote(draft, 4, 1);
    assert.equal(layoutTabMeasures(draft, 4).length, 2);
    assert.match(serializeTabDraft(draft), /\\|/);
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
