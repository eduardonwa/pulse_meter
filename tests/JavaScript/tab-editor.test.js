import test from 'node:test'
import assert from 'node:assert/strict'
import { createTabDraft, appendTabNote, serializeTabDraft } from '../../resources/js/metronome/alphatab/tab-editor.js'

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
