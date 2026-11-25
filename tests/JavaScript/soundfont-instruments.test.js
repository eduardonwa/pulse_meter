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
    [29, '/soundfont/overdrive-gtr.sf2'],
    [30, '/soundfont/metal-master.sf2'],
]

test('selected guitar instruments retain their SoundFont mapping', () => {
    for (const [instrument, file] of expected) {
        assert.equal(guitarFontForInstrument(instrument), file)
    }
})

test('custom guitars round-trip as saved instruments but play on preset 0', () => {
    for (const instrument of [28, 29, 30]) {
        const draft = {
            ...createTabDraft(), instrument,
            beats: [{ duration: 4, fret: 5, string: 6 }],
        }
        const saved = serializeTabDraft(draft)
        assert.equal(scoreGuitarInstrument(saved), instrument)
        assert.equal(parseTabDraft(saved).draft.instrument, instrument)
        const playback = soundFontPlaybackTex(saved)
        assert.match(playback, /^\\instrument 0$/m)
        assert.match(playback, /^\\tuning \(E4 B3 G3 D3 A2 E2\)$/m)
        assert.match(playback, /5\.6/)
        assert.equal(scoreGuitarInstrument(saved), instrument)
    }
})

test('Sonivox guitar notation is unchanged', () => {
    const draft = {
        ...createTabDraft(), instrument: 25,
        beats: [{ duration: 4, fret: 5, string: 6 }],
    }
    const saved = serializeTabDraft(draft)
    assert.equal(soundFontPlaybackTex(saved), saved)
})
