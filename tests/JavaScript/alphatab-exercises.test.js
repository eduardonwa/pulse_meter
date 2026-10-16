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

test('preview loop preference is explicit in exercise controls', async () => {
    const { alphaTabExerciseControls } = await import('../../resources/js/metronome/alphatab/exercise-controls.js')
    const controls = alphaTabExerciseControls()
    const element = {}
    let playDetail = null
    let loopDetail = null
    window.addEventListener('alphatab:play-pause', event => { playDetail = event.detail }, { once: true })
    controls.playPause(element)
    assert.deepEqual(playDetail, { element, loop: false })
    window.addEventListener('alphatab:toggle-loop', event => { loopDetail = event.detail }, { once: true })
    controls.toggleLoop(element)
    assert.deepEqual(loopDetail, { element, enabled: true })
    controls.playPause(element)
    assert.deepEqual(playDetail, { element, loop: false })
})
