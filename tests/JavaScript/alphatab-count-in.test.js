import test from 'node:test'
import assert from 'node:assert/strict'
import {
    ALPHATAB_COUNT_IN_KEY,
    isAlphaTabCountInEnabled,
    setAlphaTabCountInEnabled,
} from '../../resources/js/metronome/alphatab/count-in-preference.js'
import { playbackSession } from '../../resources/js/metronome/playback-session.js'

function mockStorage() {
    const values = new Map()
    return {
        getItem: key => values.get(key) ?? null,
        setItem: (key, value) => values.set(key, value),
    }
}

test('count-in is enabled by default and can be disabled and re-enabled', () => {
    const storage = mockStorage()
    assert.equal(isAlphaTabCountInEnabled(storage), true)
    assert.equal(setAlphaTabCountInEnabled(false, storage), true)
    assert.equal(storage.getItem(ALPHATAB_COUNT_IN_KEY), 'false')
    assert.equal(isAlphaTabCountInEnabled(storage), false)
    assert.equal(setAlphaTabCountInEnabled(true, storage), true)
    assert.equal(isAlphaTabCountInEnabled(storage), true)
})

test('count-in defaults to on even when storage is blocked', () => {
    const blocked = { getItem() { throw new Error('Denied') }, setItem() { throw new Error('Denied') } }
    assert.equal(isAlphaTabCountInEnabled(blocked), true)
    assert.equal(setAlphaTabCountInEnabled(false, blocked), false)
})

test('timer and tracking wait for score start and ignore count-in playback', () => {
    const session = playbackSession()
    let timers = 0
    let tracked = 0
    Object.assign(session, {
        activeExerciseIndex: 2,
        isPlaying: true,
        playbackSource: 'exercise',
        pendingAlphaTabStart: { index: 2, duration: 60, mode: 'drill' },
        pendingAlphaTabTracking: { source: 'exercise' },
        startTimer(duration) { assert.equal(duration, 60); timers++ },
        beginPlaybackTracking() { tracked++ },
    })
    session.handleAlphaTabPlaybackState({ detail: { playing: true, element: { dataset: { exerciseIndex: '2' } } } })
    assert.equal(timers, 0)
    assert.equal(tracked, 0)
    session.handleAlphaTabScoreStarted({ detail: { element: { dataset: { exerciseIndex: '1' } } } })
    assert.equal(timers, 0)
    session.handleAlphaTabScoreStarted({ detail: { element: { dataset: { exerciseIndex: '2' } } } })
    session.handleAlphaTabScoreStarted({ detail: { element: { dataset: { exerciseIndex: '2' } } } })
    assert.equal(timers, 1)
    assert.equal(tracked, 1)
    assert.equal(session.pendingAlphaTabStart, null)
})
