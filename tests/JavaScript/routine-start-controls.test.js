import assert from 'node:assert/strict'
import test from 'node:test'

import { exercises } from '../../resources/js/metronome/exercises.js'

global.window = new EventTarget()

test('Start Routine begins at the first exercise', () => {
    const player = exercises()
    const started = []

    Object.assign(player, {
        steps: [{ name: 'Classic' }, { name: 'Drill' }],
        isPlaying: false,
        currentIndex: 1,
        stop() {},
        startExercise(index) { started.push(index) },
        $nextTick(callback) { callback() },
    })

    player.toggleRoutinePlayback()

    assert.deepEqual(started, [0])
    assert.equal(player.currentIndex, 0)
})

test('Stop Routine stops playback instead of restarting the exercise', () => {
    const player = exercises()
    const started = []
    let stopped = false

    Object.assign(player, {
        steps: [{ name: 'Classic' }],
        isPlaying: true,
        stop() { stopped = true },
        startExercise(index) { started.push(index) },
        $nextTick(callback) { callback() },
    })

    player.toggleRoutinePlayback()

    assert.equal(stopped, true)
    assert.deepEqual(started, [])
})

test('Start Routine does not start an empty list', () => {
    const player = exercises()
    let started = false

    Object.assign(player, {
        steps: [],
        isPlaying: false,
        startExercise() { started = true },
    })

    player.toggleRoutinePlayback()

    assert.equal(started, false)
})
