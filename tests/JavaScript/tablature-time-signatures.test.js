import test from 'node:test'
import assert from 'node:assert/strict'
import {
    createTabDraft,
    layoutTabMeasures,
    serializeTabDraft,
    parseTabDraft,
    playbackAlphaTex,
} from '../../resources/js/metronome/alphatab/tab-editor.js'

const sevenEighthNotes = {
    ...createTabDraft(),
    beats: Array.from({ length: 7 }, (_, fret) => ({
        fret,
        string: 1,
        duration: 8,
    })),
}

test('7/8 holds seven eighth notes per bar', () => {
    const [measure] = layoutTabMeasures(sevenEighthNotes, 7, 8)
    assert.equal(measure.capacity, 14)
    assert.equal(measure.used, 14)
    assert.equal(measure.complete, true)
})

test('7/8 tablature round-trips through alphaTex and playback', () => {
    const alphaTex = serializeTabDraft(sevenEighthNotes, 7, 120, 8)
    assert.ok(alphaTex.includes(String.raw`\ts 7 8`))
    const parsed = parseTabDraft(alphaTex)
    assert.equal(parsed.numerator, 7)
    assert.equal(parsed.denominator, 8)
    assert.equal(parsed.draft.beats.length, 7)
    assert.equal(playbackAlphaTex(alphaTex), alphaTex)
})

test('4/4 remains the default meter', () => {
    const draft = {
        ...createTabDraft(),
        beats: Array.from({ length: 4 }, () => ({ fret: 0, string: 1, duration: 4 })),
    }
    const alphaTex = serializeTabDraft(draft)
    assert.ok(alphaTex.includes(String.raw`\ts 4 4`))
    assert.equal(parseTabDraft(alphaTex).denominator, 4)
})
