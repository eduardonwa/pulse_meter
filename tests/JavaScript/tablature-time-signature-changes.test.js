import test from 'node:test'
import assert from 'node:assert/strict'
import {
    createTabDraft, layoutTabMeasures, tabSignatureAt, setTabMeasureSignature,
    copyTabFragment, pasteTabFragment, breaksForInsertedTabNote,
    serializeTabDraft, parseTabDraft, serializeTabPlayback, editorPreviewAlphaTex,
} from '../../resources/js/metronome/alphatab/tab-editor.js'
import { tablatureEditorMethods } from '../../resources/js/metronome/tablature-editor-controller.js'

const four = { numerator: 4, denominator: 4 }
const seven = { numerator: 7, denominator: 8 }
const note = fret => ({ fret, string: 1, duration: 8 })
const notes = count => Array.from({ length: count }, (_, i) => note(i))
const score = () => ({
    ...createTabDraft(),
    beats: [...notes(8), ...notes(7), ...notes(7), ...notes(8)],
    breaks: [8, 15, 22],
    signatures: { 1: seven, 3: four },
    tempos: { 1: 150, 3: 120 },
})
const capacities = (draft, n = 4, d = 4) => layoutTabMeasures(draft, n, d).map(bar => bar.capacity)

function editor(draft = score()) {
    return {
        ...tablatureEditorMethods(),
        tabDraft: draft,
        stepForm: { bpm: 120, time_signature_numerator: 4, time_signature_denominator: 4 },
        tabSelectedIndex: 8,
        tabSelectedIndices: [8],
        tabSelectionAnchor: 8,
        tabInsertAtBar: false,
        tabUndoHistory: [],
        tabRedoHistory: [],
        $refs: {},
        $nextTick: callback => callback(),
        selectTabEditorCell(index) { this.tabSelectedIndex = index; this.tabSelectedIndices = [index] },
    }
}

test('meter changes inherit until the next explicit instruction and do not alter notes', () => {
    const draft = score()
    assert.deepEqual(capacities(draft), [16, 14, 14, 16])
    assert.ok(layoutTabMeasures(draft).every(bar => bar.complete))
    const edited = setTabMeasureSignature(draft, 1, { numerator: 5, denominator: 4 })
    assert.deepEqual(capacities(edited), [16, 20, 20, 16])
    assert.deepEqual(edited.beats, draft.beats)
    assert.deepEqual(edited.tempos, draft.tempos)
    assert.deepEqual(capacities(setTabMeasureSignature(edited, 1, null)), [16, 16, 16, 16])
    assert.deepEqual(tabSignatureAt(draft, 2), seven)
    for (const invalid of [{ numerator: 0, denominator: 4 }, { numerator: 17, denominator: 4 }, { numerator: 7, denominator: 3 }]) {
        assert.throws(() => setTabMeasureSignature(draft, 1, invalid), RangeError)
    }
    assert.throws(() => setTabMeasureSignature(draft, 4, seven), RangeError)
})

test('AlphaTex round trip preserves simultaneous tempo and meter changes', () => {
    const draft = score()
    const tex = serializeTabDraft(draft, 4, 120, 4)
    assert.match(tex, /\| \\ts 7 8 \\tempo 150 /)
    const parsed = parseTabDraft(tex)
    assert.deepEqual(parsed.draft.signatures, draft.signatures)
    assert.deepEqual(parsed.draft.tempos, draft.tempos)
    assert.deepEqual(parsed.draft.beats, draft.beats)
    assert.equal(serializeTabPlayback(draft, 4, 120, 4), tex)
    assert.equal(serializeTabDraft(parsed.draft, parsed.numerator, parsed.bpm, parsed.denominator), tex)
})

test('invalid meters warn without hiding overflowing notes in the editor', () => {
    const draft = setTabMeasureSignature(score(), 1, { numerator: 6, denominator: 8 })
    const state = editor(draft)
    assert.equal(layoutTabMeasures(draft)[1].used, 14)
    assert.equal(layoutTabMeasures(draft)[1].capacity, 12)
    assert.equal(state.tabEditorMeasureWarnings()[1].status, 'overflow')
    const tex = serializeTabDraft(draft, 4, 120, 4)
    assert.equal(editorPreviewAlphaTex(tex, true), tex)
    const playback = parseTabDraft(editorPreviewAlphaTex(tex, false))
    assert.equal(layoutTabMeasures(playback.draft)[1].notes.length, 6)
    assert.equal(layoutTabMeasures(draft)[1].notes.length, 7)
})

test('whole rests use the local meter, and note insertion respects it', () => {
    const draft = { ...createTabDraft(), beats: [...notes(8), ...notes(7)], breaks: [8], signatures: { 1: seven } }
    assert.deepEqual(breaksForInsertedTabNote(draft, 15, 8), [8, 15])
    const rest = { ...draft, beats: [...notes(8), { rest: true, duration: 1 }] }
    assert.ok(layoutTabMeasures(rest)[1].complete)
    assert.equal(layoutTabMeasures(rest)[1].used, 14)
})

test('whole-bar clipboard retains effective meter, partial clipboard does not', () => {
    const draft = score()
    const full = copyTabFragment(draft, [15, 16, 17, 18, 19, 20, 21])
    const partial = copyTabFragment(draft, [15])
    assert.deepEqual(full[0].signature, seven)
    assert.equal(partial[0].signature, undefined)
    const destination = { ...createTabDraft(), beats: [...notes(8), { rest: true, duration: 1 }, ...notes(8)], breaks: [8, 9] }
    const pasted = pasteTabFragment(destination, 8, full).draft
    assert.deepEqual(capacities(pasted), [16, 14, 16])
    assert.ok(layoutTabMeasures(pasted).every(bar => bar.complete))
    assert.deepEqual(pasteTabFragment(destination, 8, partial).draft.signatures, {})
})

test('pasting a mixed-meter bar at the start restores the original meter on surviving bars', () => {
    const state = editor()
    state.tabClipboard = copyTabFragment(state.tabDraft, [8, 9, 10, 11, 12, 13, 14])
    state.tabSelectedIndex = 0
    state.tabSelectedIndices = [0]
    state.pasteTabEditorSelection()
    assert.equal(state.stepForm.time_signature_numerator, 7)
    assert.equal(state.stepForm.time_signature_denominator, 8)
    assert.deepEqual(capacities(state.tabDraft, 7, 8), [14, 16, 14, 14, 16])
    state.undoTabEditorChange()
    assert.equal(state.stepForm.time_signature_numerator, 4)
    assert.equal(state.stepForm.time_signature_denominator, 4)
    assert.deepEqual(state.tabDraft.signatures, score().signatures)
})

test('deleting a meter-bearing bar shifts both kinds of measure metadata', () => {
    const state = editor()
    state.deleteTabEditorMeasure()
    assert.deepEqual(state.tabDraft.signatures, { 2: four })
    assert.deepEqual(state.tabDraft.tempos, { 2: 120 })
    assert.deepEqual(capacities(state.tabDraft), [16, 16, 16])
    state.undoTabEditorChange()
    assert.deepEqual(state.tabDraft.signatures, score().signatures)
    state.redoTabEditorChange()
    assert.deepEqual(state.tabDraft.signatures, { 2: four })
})

test('deleting the first bar promotes the next explicit meter and tempo', () => {
    const state = editor()
    state.tabSelectedIndex = 0
    state.tabSelectedIndices = [0]
    state.deleteTabEditorMeasure()
    assert.equal(state.stepForm.time_signature_numerator, 7)
    assert.equal(state.stepForm.time_signature_denominator, 8)
    assert.equal(state.stepForm.bpm, 150)
    assert.deepEqual(state.tabDraft.signatures, { 2: four })
    assert.deepEqual(capacities(state.tabDraft, 7, 8), [14, 14, 16])
})

test('meter editing and removal support undo and redo', () => {
    const state = editor()
    state.changeTabEditorSignature(5, 4)
    assert.deepEqual(state.tabEditorCurrentSignature(), { numerator: 5, denominator: 4 })
    state.removeTabEditorSignatureChange()
    assert.deepEqual(state.tabEditorCurrentSignature(), four)
    state.undoTabEditorChange()
    assert.deepEqual(state.tabEditorCurrentSignature(), { numerator: 5, denominator: 4 })
    state.undoTabEditorChange()
    assert.deepEqual(state.tabEditorCurrentSignature(), seven)
    state.redoTabEditorChange()
    assert.deepEqual(state.tabEditorCurrentSignature(), { numerator: 5, denominator: 4 })
    state.redoTabEditorChange()
    assert.deepEqual(state.tabEditorCurrentSignature(), four)
})

test('initial meter changes are included in undo and redo', () => {
    const state = editor()
    state.tabSelectedIndex = 0
    state.tabSelectedIndices = [0]
    state.changeTabEditorSignature(3, 4)
    assert.deepEqual(state.tabEditorCurrentSignature(), { numerator: 3, denominator: 4 })
    state.undoTabEditorChange()
    assert.deepEqual(state.tabEditorCurrentSignature(), four)
    state.redoTabEditorChange()
    assert.deepEqual(state.tabEditorCurrentSignature(), { numerator: 3, denominator: 4 })
    assert.deepEqual(state.tabDraft.signatures, score().signatures)
})
