import assert from 'node:assert/strict'
import test from 'node:test'

import { playbackSession } from '../../resources/js/metronome/playback-session.js'

test('stop cancels a pending BPM restart', async () => {
    const session = playbackSession()

    let restartCount = 0
    let saveCount = 0

    Object.assign(session, {
        isPlaying: true,
        activeExerciseIndex: null,
        metronome: {
            bpm: 140,
        },

        endPlaybackTracking() {},
        stopMetronome() {},

        restartMetronome() {
            restartCount++
        },

        saveCurrentSession() {
            saveCount++
        },
    })

    session.handleBpmChange()

    assert.notEqual(session.bpmChangeTimeoutId, null)

    session.stop()

    assert.equal(session.bpmChangeTimeoutId, null)

    await new Promise((resolve) => {
        setTimeout(resolve, 550)
    })

    assert.equal(restartCount, 0)
    assert.equal(saveCount, 0)
    assert.equal(session.isPlaying, false)
})