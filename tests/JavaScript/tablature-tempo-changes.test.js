import test from 'node:test'
import assert from 'node:assert/strict'
import {
    createTabDraft, tabTempoAt, setTabMeasureTempo, shiftTabMeasureTempos,
    copyTabFragment, pasteTabFragment, serializeTabDraft, parseTabDraft,
    serializeTabPlayback, breaksForInsertedTabNote,
} from '../../resources/js/metronome/alphatab/tab-editor.js'
import { tablatureEditorMethods } from '../../resources/js/metronome/tablature-editor-controller.js'

const note = fret => ({ fret, string: 1, duration: 4 })
const score = () => ({
    ...createTabDraft(),
    beats: Array.from({ length: 16 }, (_, i) => note(i)),
    breaks: [4, 8, 12],
    tempos: { 1: 183, 3: 190 },
})
const effective = draft => [0, 1, 2, 3].map(i => tabTempoAt(draft, i, 120))

function editor(draft = score()) {
    return {
        ...tablatureEditorMethods(),
        tabDraft: draft,
        stepForm: { bpm: 120, time_signature_numerator: 4, time_signature_denominator: 4 },
        tabSelectedIndex: 4,
        tabSelectedIndices: [4],
        tabSelectionAnchor: 4,
        tabInsertAtBar: false,
        tabUndoHistory: [],
        tabRedoHistory: [],
        $refs: {},
        $nextTick: callback => callback(),
        selectTabEditorCell(index) { this.tabSelectedIndex = index; this.tabSelectedIndices = [index] },
    }
}

test('measure tempo is sparse, inherited, editable and removable', () => {
    const draft = score()
    assert.deepEqual(effective(draft), [120, 183, 183, 190])
    const edited = setTabMeasureTempo(draft, 1, 200)
    assert.deepEqual(effective(edited), [120, 200, 200, 190])
    assert.deepEqual(effective(setTabMeasureTempo(edited, 1, null)), [120, 120, 120, 190])
    assert.deepEqual(draft.tempos, { 1: 183, 3: 190 })
    for (const bpm of [29, 401, 120.5, NaN]) {
        assert.throws(() => setTabMeasureTempo(draft, 1, bpm), RangeError)
    }
    assert.throws(() => setTabMeasureTempo(draft, 4, 150), RangeError)
})

test('AlphaTex round trip preserves initial BPM and measure tempo changes', () => {
    const draft = score()
    const tex = serializeTabDraft(draft, 4, 120, 4)
    assert.match(tex, /\\tempo 120\n/)
    assert.match(tex, /\| \\tempo 183 :4/)
    assert.match(tex, /\| \\tempo 190 :4/)
    const parsed = parseTabDraft(tex)
    assert.equal(parsed.bpm, 120)
    assert.deepEqual(parsed.draft.tempos, draft.tempos)
    assert.deepEqual(parsed.draft.beats, draft.beats)
    assert.equal(serializeTabDraft(parsed.draft, 4, parsed.bpm, 4), tex)
    assert.equal(serializeTabPlayback(draft, 4, 120, 4), tex)
})

test('legacy single-tempo scores remain editable', () => {
    const draft = score()
    delete draft.tempos
    const tex = serializeTabDraft(draft, 4, 120, 4)
    assert.deepEqual(parseTabDraft(tex).draft.tempos, {})
    assert.equal((tex.match(/\\tempo/g) ?? []).length, 1)
})

test('whole-measure clipboard copies explicit tempo, partial selection does not', () => {
    const draft = score()
    const full = copyTabFragment(draft, [4, 5, 6, 7])
    const partial = copyTabFragment(draft, [4])
    assert.equal(full[0].tempo, 183)
    assert.equal(partial[0].tempo, undefined)
    assert.equal(copyTabFragment(draft, [8, 9, 10, 11])[0].tempo, undefined)
    const destination = { ...createTabDraft(), beats: [...draft.beats.slice(0, 4), { rest: true, duration: 1 }], breaks: [4] }
    assert.deepEqual(pasteTabFragment(destination, 4, full).draft.tempos, { 1: 183 })
    assert.deepEqual(pasteTabFragment(destination, 4, partial).draft.tempos, {})
})

test('pasting a complete measure shifts later explicit tempo instructions', () => {
    const draft = score()
    const fragment = copyTabFragment(draft, [4, 5, 6, 7])
    const pasted = pasteTabFragment(draft, 8, fragment, 4, 4, true)
    assert.deepEqual(pasted.draft.tempos, { 1: 183, 2: 183, 4: 190 })
    assert.deepEqual(pasted.draft.breaks, [4, 8, 12, 16])
})

test('inserting notes within a measure leaves tempo changes on their original measures', () => {
    const draft = score()
    const breaks = breaksForInsertedTabNote(draft, 2, 4)
    const beats = [...draft.beats]
    beats.splice(2, 0, note(17))
    const edited = { ...draft, beats, breaks }
    assert.deepEqual(breaks, [5, 9, 13])
    assert.deepEqual(effective(edited), [120, 183, 183, 190])
})

test('removing a tempo-bearing measure removes its instruction and shifts later changes', () => {
    const state = editor()
    state.deleteTabEditorMeasure()
    assert.deepEqual(state.tabDraft.tempos, { 2: 190 })
    assert.deepEqual(state.tabDraft.breaks, [4, 8])
    assert.equal(tabTempoAt(state.tabDraft, 1, 120), 120)
    assert.deepEqual(shiftTabMeasureTempos({ 1: 183, 3: 190 }, 1, 1), { 2: 190 })
    state.undoTabEditorChange()
    assert.deepEqual(state.tabDraft.tempos, { 1: 183, 3: 190 })
    state.redoTabEditorChange()
    assert.deepEqual(state.tabDraft.tempos, { 2: 190 })
})

test('tempo edits and removal participate in undo and redo', () => {
    const state = editor()
    state.changeTabEditorBpm(200)
    assert.equal(state.tabEditorCurrentBpm(), 200)
    state.removeTabEditorTempoChange()
    assert.equal(state.tabEditorCurrentBpm(), 120)
    state.undoTabEditorChange()
    assert.equal(state.tabEditorCurrentBpm(), 200)
    state.undoTabEditorChange()
    assert.equal(state.tabEditorCurrentBpm(), 183)
    state.redoTabEditorChange()
    assert.equal(state.tabEditorCurrentBpm(), 200)
    state.redoTabEditorChange()
    assert.equal(state.tabEditorCurrentBpm(), 120)
})

test('initial BPM changes are also restored by undo and redo', () => {
    const state = editor()
    state.tabSelectedIndex = 0
    state.tabSelectedIndices = [0]
    state.changeTabEditorBpm(140)
    assert.equal(state.stepForm.bpm, 140)
    state.undoTabEditorChange()
    assert.equal(state.stepForm.bpm, 120)
    state.redoTabEditorChange()
    assert.equal(state.stepForm.bpm, 140)
    assert.deepEqual(state.tabDraft.tempos, { 1: 183, 3: 190 })
})
