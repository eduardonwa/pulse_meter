import test from 'node:test'
import assert from 'node:assert/strict'
import { exercises } from '../../resources/js/metronome/exercises.js'
import { createTabDraft, changeSelectedTabDurations } from '../../resources/js/metronome/alphatab/tab-editor.js'

function editor() {
    const instance = exercises()
    instance.stepForm = { time_signature_numerator: 7, time_signature_denominator: 8 }
    instance.tabDraft = {
        ...createTabDraft(),
        beats: Array.from({ length: 7 }, (_, fret) => ({ fret, string: 1, duration: 8 })),
    }
    return instance
}

test('full final 7/8 measure retains the normal trailing cursor', () => {
    const instance = editor()
    const columns = instance.tabEditorDisplayColumns()
    assert.equal(columns.filter(column => column.index === 7).length, 1)
    assert.deepEqual(columns.at(-1), { index: 7, insertion: false, measureEnd: false })
})

test('changing all note durations reveals a final insertion slot', () => {
    const instance = editor()
    instance.tabDraft.beats = changeSelectedTabDurations(
        instance.tabDraft.beats,
        [0, 1, 2, 3, 4, 5, 6],
        16,
    )
    const columns = instance.tabEditorDisplayColumns()
    assert.equal(columns.filter(column => column.index === 7).length, 1)
    assert.deepEqual(columns.at(-1), { index: 7, insertion: true, measureEnd: true })
    assert.equal(instance.tabDraft.beats.length, 7)
    assert.equal(instance.tabEditorMeasureLayout()[0].used, 7)
    assert.equal(instance.tabEditorMeasureLayout()[0].capacity, 14)
})

test('intermediate incomplete measure keeps insertion point before next measure', () => {
    const instance = editor()
    instance.tabDraft.beats = [
        ...Array.from({ length: 7 }, (_, fret) => ({ fret, string: 1, duration: 16 })),
        { fret: 10, string: 2, duration: 8 },
    ]
    instance.tabDraft.breaks = [7]
    const positions = instance.tabEditorDisplayColumns().filter(column => column.index === 7)
    assert.deepEqual(positions, [
        { index: 7, insertion: true, measureEnd: true },
        { index: 7, insertion: false, measureEnd: false },
    ])
})

test('measure boundaries are computed once for a full imported score', () => {
    const instance = editor()
    instance.tabDraft.beats = Array.from({ length: 105 }, (_, fret) => ({
        fret: fret % 20, string: 1, duration: 8,
    }))
    instance.tabDraft.breaks = Array.from({ length: 14 }, (_, index) => (index + 1) * 7)
    let layouts = 0
    const original = instance.tabEditorMeasureLayout.bind(instance)
    instance.tabEditorMeasureLayout = () => {
        layouts++
        return original()
    }
    const columns = instance.tabEditorDisplayColumns()
    assert.equal(layouts, 1)
    assert.equal(columns.length, 106)
    assert.equal(columns.filter(column => column.measureEnd).length, 15)
})

test('validation log reports one success message for complete measures', () => {
    const instance = editor()
    instance.tabDraft.beats = [
        ...instance.tabDraft.beats,
        ...instance.tabDraft.beats,
    ]
    instance.tabDraft.breaks = [7]
    assert.equal(instance.tabEditorMeasureStatus(), '2 measure(s) · all complete')
    assert.deepEqual(instance.tabEditorMeasureIssues(), [])
})

test('validation log lists only incomplete and overfull measures with reasons', () => {
    const instance = editor()
    instance.tabDraft.beats = [
        ...instance.tabDraft.beats,
        { fret: 8, string: 1, duration: 16 },
        ...Array.from({ length: 8 }, (_, fret) => ({ fret, string: 1, duration: 8 })),
    ]
    instance.tabDraft.breaks = [7, 8]
    const issues = instance.tabEditorMeasureIssues()
    assert.equal(issues.length, 2)
    assert.deepEqual(issues.map(issue => [issue.number, issue.status]), [[2, 'incomplete'], [3, 'overflow']])
    assert.match(issues[0].message, /Measure 2: .* remaining/)
    assert.match(issues[1].message, /Measure 3: .* over limit/)
    assert.equal(instance.tabEditorMeasureWarnings()[0].status, 'complete')
})

test('adding a sixteenth rest at the final + completes a measure with one unit remaining', () => {
    const instance = editor()
    instance.stepForm = { time_signature_numerator: 4, time_signature_denominator: 4 }
    instance.tabDraft = {
        ...createTabDraft(),
        duration: 16,
        beats: [
            ...Array.from({ length: 7 }, (_, fret) => ({ fret, string: 6, duration: 8 })),
            { fret: 7, string: 6, duration: 16 },
        ],
        breaks: [],
    }
    assert.equal(instance.tabEditorMeasureLayout()[0].used, 15)
    const slot = instance.tabEditorDisplayColumns().at(-1)
    assert.deepEqual(slot, { index: 8, insertion: true, measureEnd: true })
    instance.selectTabEditorInsertion(slot.index, 6)
    instance.addTabEditorRest()
    assert.equal(instance.tabDraft.beats.length, 9)
    assert.deepEqual(instance.tabDraft.beats[8], { rest: true, duration: 16 })
    assert.equal(instance.tabEditorMeasureLayout()[0].used, 16)
    assert.equal(instance.tabEditorMeasureStatus(), '1 measure(s) · all complete')
})

test('inserting a final rest clears inherited triplet when only a sixteenth remains', () => {
    const instance = editor()
    instance.stepForm = { time_signature_numerator: 4, time_signature_denominator: 4 }
    instance.tabDraft = {
        ...createTabDraft(),
        duration: 16,
        tuplet: 3,
        beats: [
            ...Array.from({ length: 7 }, (_, fret) => ({ fret, string: 6, duration: 8 })),
            { fret: 7, string: 6, duration: 16 },
        ],
        breaks: [],
    }
    instance.selectTabEditorInsertion(8, 6)
    instance.addTabEditorRest()
    assert.deepEqual(instance.tabDraft.beats.at(-1), { rest: true, duration: 16 })
    assert.equal(instance.tabEditorMeasureStatus(), '1 measure(s) · all complete')
})

test('R keyboard shortcut inserts a sixteenth rest at the final insertion slot', () => {
    const instance = editor()
    instance.stepForm = { time_signature_numerator: 4, time_signature_denominator: 4 }
    instance.tabDraft = {
        ...createTabDraft(),
        duration: 16,
        beats: [
            ...Array.from({ length: 7 }, (_, fret) => ({ fret, string: 6, duration: 8 })),
            { fret: 7, string: 6, duration: 16 },
        ],
        breaks: [],
    }
    instance.selectTabEditorInsertion(8, 6)
    let prevented = false
    instance.handleTabEditorKey({
        key: 'r',
        target: { tagName: 'DIV' },
        ctrlKey: false, metaKey: false, altKey: false, repeat: false,
        preventDefault() { prevented = true },
    })
    assert.equal(prevented, true)
    assert.deepEqual(instance.tabDraft.beats.at(-1), { rest: true, duration: 16 })
    assert.equal(instance.tabEditorMeasureStatus(), '1 measure(s) · all complete')
})

test('Score click selects the corresponding note using the existing tab editor model', () => {
    const instance = editor()
    const preview = {}
    instance.$refs = { tabEditorPreview: preview, tabEditorScoreFocus: { focus() {} } }
    instance.$nextTick = callback => callback()
    instance.tabScoreView = 'score'
    instance.tabFullMusicXml = null
    instance.tabDraft.breaks = []
    instance.selectTabEditorScoreBeat({
        detail: { element: preview, measureIndex: 0, beatIndex: 2, string: 1 },
    })
    assert.equal(instance.tabSelectedIndex, 2)
    assert.equal(instance.tabDraft.string, 1)
    assert.deepEqual(instance.tabSelectedIndices, [2])
})

test('Score click is ignored when viewing the editable Grid', () => {
    const instance = editor()
    const preview = {}
    instance.$refs = { tabEditorPreview: preview }
    instance.tabScoreView = 'grid'
    instance.tabSelectedIndex = 0
    instance.selectTabEditorScoreBeat({
        detail: { element: preview, measureIndex: 0, beatIndex: 3, string: 1 },
    })
    assert.equal(instance.tabSelectedIndex, 0)
})

test('Score insert action reuses note insertion after the selected beat', () => {
    const instance = editor()
    let focused = false
    instance.$refs = { tabEditorScoreKeyboard: { focus() { focused = true } } }
    instance.$nextTick = callback => callback()
    instance.tabScoreView = 'score'
    instance.tabFullMusicXml = null
    instance.tabSelectedIndex = 2
    instance.tabSelectedIndices = [2]
    instance.prepareTabEditorScoreInsertion()
    assert.equal(instance.tabInsertAtBar, true)
    assert.equal(instance.tabSelectedIndex, 3)
    assert.equal(focused, true)
    instance.tabDraft = { ...instance.tabDraft, string: 1, fret: 9 }
    instance.addTabEditorNote()
    assert.equal(instance.tabDraft.beats.length, 8)
    assert.equal(instance.tabDraft.beats[3].fret, 9)
    assert.equal(instance.tabDraft.beats[4].fret, 3)
})

test('Score I shortcut creates an insertion caret without modifying notes', () => {
    const instance = editor()
    instance.$refs = { tabEditorScoreKeyboard: { focus() {} } }
    instance.$nextTick = callback => callback()
    instance.tabScoreView = 'score'
    instance.tabFullMusicXml = null
    instance.tabSelectedIndex = 1
    instance.tabSelectedIndices = [1]
    let prevented = false
    instance.handleTabEditorKey({
        key: 'i', target: { tagName: 'DIV' },
        ctrlKey: false, metaKey: false, altKey: false, repeat: false,
        preventDefault() { prevented = true },
    })
    assert.equal(prevented, true)
    assert.equal(instance.tabDraft.beats.length, 7)
    assert.equal(instance.tabSelectedIndex, 2)
    assert.equal(instance.tabInsertAtBar, true)
})

function scoreEditorForAudit(beats, breaks = []) {
    const instance = editor()
    instance.stepForm = { time_signature_numerator: 4, time_signature_denominator: 4 }
    instance.tabScoreView = 'score'
    instance.tabDraft = { ...createTabDraft(), beats, breaks }
    instance.$refs = {}
    instance.$nextTick = callback => callback()
    return instance
}

test('deleting the entire score leaves one editable measure and can be undone', () => {
    const instance = scoreEditorForAudit([{ fret: 3, string: 6, duration: 8 }])
    instance.selectTabEditorCell(0, 6)
    instance.undoTabEditorNote()
    assert.deepEqual(instance.tabDraft.beats, [{ rest: true, duration: 1 }])
    instance.undoTabEditorChange()
    assert.equal(instance.tabDraft.beats[0].fret, 3)
});

test('automatic bar creation belongs to the same undo operation as note entry', () => {
    const instance = scoreEditorForAudit([
        ...Array.from({ length: 7 }, () => ({ fret: 3, string: 6, duration: 8 })),
    ])
    instance.tabSelectedIndex = 7
    instance.tabDraft = { ...instance.tabDraft, fret: 5, string: 6, duration: 8 }
    instance.addTabEditorNote()
    assert.equal(instance.tabEditorMeasureLayout().length, 2)
    assert.equal(instance.tabUndoHistory.length, 1)
    instance.undoTabEditorChange()
    assert.equal(instance.tabDraft.beats.length, 7)
    instance.redoTabEditorChange()
    assert.equal(instance.tabEditorMeasureLayout().length, 2)
});

test('measure deletion at an insertion slot targets the preceding incomplete bar', () => {
    const instance = scoreEditorForAudit([
        { fret: 3, string: 6, duration: 8 },
        { fret: 9, string: 6, duration: 8 },
    ], [1])
    instance.tabSelectedIndex = 1
    instance.tabInsertAtBar = true
    instance.deleteTabEditorMeasure()
    assert.deepEqual(instance.tabDraft.beats.map(beat => beat.fret), [9])
    assert.deepEqual(instance.tabDraft.breaks, [])
});

test('undo restores the actual range anchor rather than its last selected index', () => {
    const instance = scoreEditorForAudit(Array.from({ length: 4 }, (_, fret) =>
        ({ fret, string: 6, duration: 8 })))
    instance.selectTabEditorCell(0, 6)
    instance.selectTabEditorCell(2, 6, { shiftKey: true })
    instance.changeTabEditorDuration(16)
    instance.undoTabEditorChange()
    assert.equal(instance.tabSelectionAnchor, 0)
    instance.selectTabEditorCell(1, 6, { shiftKey: true })
    assert.deepEqual(instance.tabSelectedIndices, [0, 1])
});
