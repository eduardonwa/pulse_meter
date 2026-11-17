import test from 'node:test'
import assert from 'node:assert/strict'
import {
    insertTabRestAt,
    layoutTabMeasures,
    updateTabBreaks,
} from '../../resources/js/metronome/alphatab/tab-editor.js'

function draft() {
    return {
        beats: [
            { fret: 17, string: 2, duration: 16 },
            { fret: 19, string: 2, duration: 16 },
            { fret: 20, string: 2, duration: 16 },
            { fret: 17, string: 1, duration: 16 },
        ],
        breaks: [],
        duration: 16,
    }
}

test('insert before adds a rest without overwriting existing frets', () => {
    const inserted = insertTabRestAt(draft(), 2)
    assert.deepEqual(inserted.beats.map(beat => beat.rest ? 'rest' : beat.fret),
        [17, 19, 'rest', 20, 17])
    assert.equal(inserted.beats[2].duration, 16)
    assert.deepEqual(inserted.breaks, [])
})

test('insert after the last note adds an editable final position', () => {
    const inserted = insertTabRestAt(draft(), 4)
    assert.equal(inserted.beats.length, 5)
    assert.deepEqual(inserted.beats.at(-1), { rest: true, duration: 16 })
})

test('insert before the first note keeps the original notes', () => {
    const inserted = insertTabRestAt(draft(), 0)
    assert.deepEqual(inserted.beats.slice(1), draft().beats)
})

test('insertion shifts later measure boundaries without inserting new bars', () => {
    const original = { ...draft(), breaks: [2] }
    const inserted = insertTabRestAt(original, 1)
    assert.deepEqual(inserted.breaks, [3])
    assert.deepEqual(layoutTabMeasures(inserted, 4, 4).map(m => m.notes.length), [3, 2])
    assert.deepEqual(updateTabBreaks([2], 1, 'insert', 5), [3])
})

test('insertion rejects invalid positions and unsupported note lengths', () => {
    assert.throws(() => insertTabRestAt(draft(), -1), RangeError)
    assert.throws(() => insertTabRestAt(draft(), 6), RangeError)
    assert.throws(() => insertTabRestAt(draft(), 2, 3), RangeError)
})
