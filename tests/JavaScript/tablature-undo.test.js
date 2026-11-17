import test from 'node:test'
import assert from 'node:assert/strict'
import { exercises } from '../../resources/js/metronome/exercises.js'
import { createTabDraft } from '../../resources/js/metronome/alphatab/tab-editor.js'

function editor() {
    const e = exercises()
    e.tabDraft = { ...createTabDraft(), beats: [
        { fret: 2, string: 1, duration: 4 },
        { fret: 4, string: 1, duration: 4 },
    ] }
    e.tabSelectedIndex = 0
    return e
}

test('Ctrl+Z restores last duration change without deleting a note', () => {
    const e = editor()
    e.tabSelectedIndices = [0]
    e.changeTabEditorDuration(8)
    assert.equal(e.tabDraft.beats[0].duration, 8)
    assert.equal(e.tabUndoHistory.length, 1)
    let prevented = false
    e.handleTabEditorKey({ key: 'z', ctrlKey: true, metaKey: false, shiftKey: false,
        target: { tagName: 'DIV' }, preventDefault() { prevented = true } })
    assert.equal(prevented, true)
    assert.equal(e.tabDraft.beats[0].duration, 4)
    assert.equal(e.tabDraft.beats.length, 2)
})

test('Undo restores notes deleted with the Delete action', () => {
    const e = editor()
    e.undoTabEditorNote()
    assert.equal(e.tabDraft.beats.length, 1)
    e.undoTabEditorChange()
    assert.deepEqual(e.tabDraft.beats.map(b => b.fret), [2, 4])
})

test('Undo restores dotted and triplet edits', () => {
    const e = editor()
    e.tabSelectedIndices = [0]
    e.setTabEditorRhythm({ dotted: true })
    e.setTabEditorRhythm({ tuplet: 3 })
    e.undoTabEditorChange()
    assert.equal(e.tabDraft.beats[0].dotted, true)
    assert.equal(e.tabDraft.beats[0].tuplet, undefined)
    e.undoTabEditorChange()
    assert.equal(e.tabDraft.beats[0].dotted, undefined)
})

test('Undo history is bounded', () => {
    const e = editor()
    for (let i = 0; i < 70; i++) e.recordTabEditorUndo()
    assert.equal(e.tabUndoHistory.length, 50)
})

test('Delete removes all selected positions and undo/redo restores them as one operation', () => {
    const e = editor()
    e.tabDraft.beats.push({ fret: 6, string: 1, duration: 4 }, { fret: 8, string: 1, duration: 4 })
    e.tabDraft.breaks = [2]
    e.tabSelectedIndex = 3
    e.tabSelectedIndices = [1, 3]
    e.undoTabEditorNote()
    assert.deepEqual(e.tabDraft.beats.map(b => b.fret), [2, 6])
    assert.deepEqual(e.tabDraft.breaks, [1])
    assert.equal(e.tabUndoHistory.length, 1)
    e.undoTabEditorChange()
    assert.deepEqual(e.tabDraft.beats.map(b => b.fret), [2, 4, 6, 8])
    assert.deepEqual(e.tabDraft.breaks, [2])
    e.redoTabEditorChange()
    assert.deepEqual(e.tabDraft.beats.map(b => b.fret), [2, 6])
    assert.deepEqual(e.tabDraft.breaks, [1])
})

test('Backspace clears selected string across several beats without removing rhythmic positions', () => {
    const e = editor()
    e.tabDraft.beats.push({ fret: 6, string: 1, duration: 8, dotted: true })
    e.tabSelectedIndices = [0, 2]
    e.clearTabEditorNote()
    assert.deepEqual(e.tabDraft.beats, [
        { rest: true, duration: 4 },
        { fret: 4, string: 1, duration: 4 },
        { rest: true, duration: 8, dotted: true },
    ])
    e.undoTabEditorChange()
    assert.deepEqual(e.tabDraft.beats.map(b => b.fret), [2, 4, 6])
    e.redoTabEditorChange()
    assert.equal(e.tabDraft.beats[0].rest, true)
    assert.equal(e.tabDraft.beats[2].dotted, true)
})

test('Ctrl+Shift+Z and Ctrl+Y redo; a new edit clears redo history', () => {
    const e = editor()
    e.tabSelectedIndices = [0]
    e.changeTabEditorDuration(8)
    e.undoTabEditorChange()
    let prevented = false
    e.handleTabEditorKey({
        key: 'Z', ctrlKey: true, metaKey: false, shiftKey: true,
        target: { tagName: 'DIV' }, preventDefault() { prevented = true },
    })
    assert.equal(prevented, true)
    assert.equal(e.tabDraft.beats[0].duration, 8)
    e.undoTabEditorChange()
    e.handleTabEditorKey({
        key: 'y', ctrlKey: true, metaKey: false, shiftKey: false,
        target: { tagName: 'DIV' }, preventDefault() {},
    })
    assert.equal(e.tabDraft.beats[0].duration, 8)
    e.undoTabEditorChange()
    e.changeTabEditorDuration(16)
    assert.equal(e.tabRedoHistory.length, 0)
    assert.equal(e.tabDraft.beats[0].duration, 16)
})
