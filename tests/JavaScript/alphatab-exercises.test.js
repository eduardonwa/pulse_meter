import test from 'node:test'
import assert from 'node:assert/strict'

global.window = new EventTarget()

global.CustomEvent = class CustomEvent extends Event {
    constructor(type, options = {}) {
        super(type)

        this.detail = options.detail
    }
}

const {
    dispatchPlaybackState,
} = await import('../../resources/js/alphatab-exercises.js')

test('dispatches playing true when alphaTab is playing', () => {
    const element = {}

    let received = null

    window.addEventListener(
        'alphatab:playback-state',
        event => {
            received = event.detail
        },
        { once: true }
    )

    dispatchPlaybackState(
        element,
        'playing',
        'playing'
    )

    assert.deepEqual(received, {
        element,
        playing: true,
    })
})

test('dispatches playing false when alphaTab is not playing', () => {
    const element = {}

    let received = null

    window.addEventListener(
        'alphatab:playback-state',
        event => {
            received = event.detail
        },
        { once: true }
    )

    dispatchPlaybackState(
        element,
        'stopped',
        'playing'
    )

    assert.deepEqual(received, {
        element,
        playing: false,
    })
})

test('Preview always requests one-shot playback', async () => {
    const { alphaTabExerciseControls } = await import('../../resources/js/metronome/alphatab/exercise-controls.js')
    const controls = alphaTabExerciseControls()
    const previousWindow = global.window
    global.window = new EventTarget()

    try {
        const element = {}
        const received = []
        window.addEventListener('alphatab:play-pause', event => received.push(event.detail))
        controls.playPause(element)
        controls.playPause(element)
        assert.deepEqual(received, [
            { element },
            { element },
        ])
    } finally {
        global.window = previousWindow
    }
})

test('stopping preview also stops an active routine session', async () => {
    const { alphaTabExerciseControls } = await import('../../resources/js/metronome/alphatab/exercise-controls.js')
    const controls = alphaTabExerciseControls()
    const previousWindow = global.window
    global.window = new EventTarget()
    const reasons = []
    let alphaTabStopCount = 0

    try {
        Object.assign(controls, {
            isPlaying: true,
            playbackSource: 'exercise',
            stop(reason) { reasons.push(reason) },
        })
        window.addEventListener('alphatab:stop', () => alphaTabStopCount++)
        controls.stopPlayback()
        assert.deepEqual(reasons, ['manual'])
        assert.equal(alphaTabStopCount, 0)

        controls.isPlaying = false
        controls.stopPlayback()
        assert.equal(alphaTabStopCount, 1)
    } finally {
        global.window = previousWindow
    }
})
