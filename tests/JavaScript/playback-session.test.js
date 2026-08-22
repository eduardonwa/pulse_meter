import assert from 'node:assert/strict'
import test from 'node:test'

import { playbackSession } from '../../resources/js/metronome/playback-session.js'

global.window = new EventTarget()

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

test('an alphaTab exercise does not start the Dorelog metronome', () => {
    const session = playbackSession()

    let audioContextCount = 0
    let metronomeStartCount = 0

    Object.assign(session, {
        isPlaying: false,
        activeExerciseIndex: null,
        metronome: {
            bpm: 100,
            mode: 'timer',
            duration_seconds: 60,
        },

        ensureAudioContext() {
            audioContextCount++
        },

        startMetronome() {
            metronomeStartCount++
        },

        stopMetronome() {},
        endPlaybackTracking() {},
        startTimer() {},
    })

    session.startPlaybackSession({
        source: 'exercise',
        mode: 'timer',
        bpm: 180,
        duration: 60,
        exerciseIndex: 0,
        useAlphaTab: true,
    })

    assert.equal(audioContextCount, 0)
    assert.equal(metronomeStartCount, 0)
    assert.equal(session.isPlaying, true)
})
