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
