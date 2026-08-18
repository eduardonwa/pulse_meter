import assert from 'node:assert/strict'
import test from 'node:test'

import {
    audioEngine,
} from '../../resources/js/metronome/audio-engine.js'

function createDeferredPromise() {
    let resolve
    let reject

    const promise = new Promise(
        (resolvePromise, rejectPromise) => {
            resolve = resolvePromise
            reject = rejectPromise
        }
    )

    return {
        promise,
        resolve,
        reject,
    }
}

function createTestEngine() {
    const engine = audioEngine()

    engine.metronome = {
        mode: 'classic',
        bpm: 120,
    }

    engine.playPatternBeatCalls = 0

    engine.playPatternBeat = () => {
        engine.playPatternBeatCalls += 1
    }

    engine.getBeatIntervalMs = () => 500

    return engine
}

async function withFakeIntervals(callback) {
    const originalSetInterval =
        globalThis.setInterval

    const originalClearInterval =
        globalThis.clearInterval

    let nextIntervalId = 1

    const activeIntervals = new Set()

    globalThis.setInterval = () => {
        const intervalId = nextIntervalId

        nextIntervalId += 1
        activeIntervals.add(intervalId)

        return intervalId
    }

    globalThis.clearInterval = intervalId => {
        activeIntervals.delete(intervalId)
    }

    try {
        await callback(activeIntervals)
    } finally {
        globalThis.setInterval =
            originalSetInterval

        globalThis.clearInterval =
            originalClearInterval
    }
}

test(
    'stop invalidates a pending metronome start',
    async () => {
        await withFakeIntervals(
            async activeIntervals => {
                const engine =
                    createTestEngine()

                const soundLoading =
                    createDeferredPromise()

                engine.loadClickSounds = () => {
                    return soundLoading.promise
                }

                const pendingStart =
                    engine.startMetronome(120)

                /*
                 * Stop ocurre antes de que los sonidos
                 * terminen de cargar.
                 */
                engine.stopMetronome()

                soundLoading.resolve()

                await pendingStart

                assert.equal(
                    engine.intervalId,
                    null
                )

                assert.equal(
                    activeIntervals.size,
                    0
                )

                assert.equal(
                    engine.playPatternBeatCalls,
                    0
                )
            }
        )
    }
)

test(
    'an older start cannot replace a newer start',
    async () => {
        await withFakeIntervals(
            async activeIntervals => {
                const engine =
                    createTestEngine()

                const firstLoading =
                    createDeferredPromise()

                const secondLoading =
                    createDeferredPromise()

                let loadingCall = 0

                engine.loadClickSounds = () => {
                    loadingCall += 1

                    return loadingCall === 1
                        ? firstLoading.promise
                        : secondLoading.promise
                }

                /*
                 * Start A comienza primero, pero su carga
                 * tardará más.
                 */
                const firstStart =
                    engine.startMetronome(100)

                /*
                 * Start B comienza después, pero termina
                 * primero.
                 */
                const secondStart =
                    engine.startMetronome(140)

                secondLoading.resolve()

                await secondStart

                const secondIntervalId =
                    engine.intervalId

                assert.equal(
                    activeIntervals.size,
                    1
                )

                assert.equal(
                    engine.playPatternBeatCalls,
                    1
                )

                /*
                 * La carga vieja termina tarde.
                 */
                firstLoading.resolve()

                await firstStart

                /*
                 * Start A no debe crear otro intervalo
                 * ni reemplazar el intervalo de Start B.
                 */
                assert.equal(
                    engine.intervalId,
                    secondIntervalId
                )

                assert.equal(
                    activeIntervals.size,
                    1
                )

                assert.equal(
                    engine.playPatternBeatCalls,
                    1
                )

                engine.stopMetronome()
            }
        )
    }
)

test(
    'eighth-note subdivisions run at half the beat interval',
    () => {
        const engine = audioEngine()

        engine.metronome = {
            mode: 'creative',
            bpm: 120,
        }

        engine.timeSignature = {
            numerator: 4,
            denominator: 4,
        }

        engine.grouping = [4]
        engine.pattern = []
        engine.subdivision = 2

        assert.equal(
            engine.getBeatIntervalMs(120),
            500
        )

        assert.equal(
            engine.getSubdivisionIntervalMs(120),
            250
        )
    }
)

test(
    'sixteenth-note subdivisions run at one quarter of the beat interval',
    () => {
        const engine = audioEngine()

        engine.metronome = {
            mode: 'creative',
            bpm: 120,
        }

        engine.timeSignature = {
            numerator: 4,
            denominator: 4,
        }

        engine.grouping = [4]
        engine.pattern = []
        engine.subdivision = 4

        assert.equal(
            engine.getSubdivisionIntervalMs(120),
            125
        )
    }
)

test(
    'eighth-note playback advances beat then offbeat',
    () => {
        const engine = audioEngine()

        engine.metronome = {
            mode: 'creative',
            bpm: 120,
        }

        engine.timeSignature = {
            numerator: 4,
            denominator: 4,
        }

        engine.grouping = [4]
        engine.pattern = []
        engine.subdivision = 2

        engine.currentBeat = 1
        engine.currentSubdivision = 0

        engine.advancePatternPosition()

        assert.equal(engine.currentBeat, 1)
        assert.equal(engine.currentSubdivision, 1)

        engine.advancePatternPosition()

        assert.equal(engine.currentBeat, 2)
        assert.equal(engine.currentSubdivision, 0)

        engine.advancePatternPosition()

        assert.equal(engine.currentBeat, 2)
        assert.equal(engine.currentSubdivision, 1)

        engine.advancePatternPosition()

        assert.equal(engine.currentBeat, 3)
        assert.equal(engine.currentSubdivision, 0)
    }
)