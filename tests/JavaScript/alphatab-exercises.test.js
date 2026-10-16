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
    const playDetails = []
    let loopDetail = null
    const onPlay = event => playDetails.push(event.detail)
    window.addEventListener('alphatab:play-pause', onPlay)
    controls.playPause(element)
    assert.deepEqual(playDetails[0], { element, loop: false })
    window.addEventListener('alphatab:toggle-loop', event => { loopDetail = event.detail }, { once: true })
    controls.toggleLoop(element)
    assert.deepEqual(loopDetail, { element, enabled: true })
    controls.playPause(element)
    assert.deepEqual(playDetails[1], { element, loop: true })
    window.removeEventListener('alphatab:play-pause', onPlay)
})
