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