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
