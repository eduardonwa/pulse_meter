import test from 'node:test'
import assert from 'node:assert/strict'

import {
    state,
} from '../../resources/js/metronome/state.js'

import {
    audioEngine,
} from '../../resources/js/metronome/audio-engine.js'

test('the BPM picker exposes values through 400', () => {
    const appState = state()

    assert.equal(
        appState.bpmOptions[0],
        30
    )

    assert.equal(
        appState.bpmOptions.at(-1),
        400
    )

    assert.equal(
        appState.bpmOptions.length,
        371
    )
})

test('400 BPM in 4/4 produces a 150 millisecond beat interval', () => {
    const engine = audioEngine()

    engine.getPlaybackPulse = () => ({
        timeSignature: {
            numerator: 4,
            denominator: 4,
        },
    })

    assert.equal(
        engine.getBeatIntervalMs(400),
        150
    )
})