import test from 'node:test'
import assert from 'node:assert/strict'
import { createTabDraft, parseTabDraft, serializeTabDraft } from '../../resources/js/metronome/alphatab/tab-editor.js'
import {
    guitarFontForInstrument, scoreGuitarInstrument, soundFontPlaybackTex,
} from '../../resources/js/metronome/alphatab/soundfont-instruments.js'

const expected = [
    [25, '/soundfont/sonivox.sf2'],
    [27, '/soundfont/sonivox.sf2'],
    [28, '/soundfont/acoustic.sf2'],
    [29, '/soundfont/dorelog-6.sf3'],
    [30, '/soundfont/dorelog-6.sf3'],
    [31, '/soundfont/dorelog-6.sf3'],
    [32, '/soundfont/steel-gtr.sf2'],
]
test('guitar instrument IDs select their intended banks', () => {
    for (const [id, font] of expected) assert.equal(guitarFontForInstrument(id), font)
})
test('Dorelog guitar instruments preserve stored IDs and map to their SF programs', () => {
    const programs = [[28, 0], [29, 49], [30, 55], [31, 35], [32, 0]]
    for (const [id, program] of programs) {
        const draft = { ...createTabDraft(), instrument: id, beats: [{ duration: 4, fret: 5, string: 6 }] }
        const saved = serializeTabDraft(draft)
        assert.equal(scoreGuitarInstrument(saved), id)
        assert.equal(parseTabDraft(saved).draft.instrument, id)
        const playback = soundFontPlaybackTex(saved)
        assert.match(playback, new RegExp('^\\\\instrument ' + program + '$', 'm'))
        assert.match(playback, /^\\tuning \(E4 B3 G3 D3 A2 E2\)$/m)
    }
})
test('Sonivox instruments remain unaffected', () => {
    const saved = serializeTabDraft({ ...createTabDraft(), instrument: 25, beats: [{ duration: 4, fret: 5, string: 6 }] })
    assert.equal(soundFontPlaybackTex(saved), saved)
})
