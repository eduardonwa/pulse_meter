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
    'randomizer uses its own pulse during creative playback',
    () => {
        const engine = audioEngine()

        engine.metronome = {
            mode: 'creative',
            bpm: 120,
        }

        engine.creativeMode = 'randomizer'

        engine.randomizerPulse = {
            timeSignature: {
                numerator: 9,
                denominator: 8,
            },

            subdivision: 1,
            grouping: [3, 3, 3],
            pattern: [],
        }

        engine.timeSignature = {
            numerator: 4,
            denominator: 4,
        }

        engine.subdivision = 1
        engine.grouping = [4]
        engine.pattern = []

        assert.equal(
            engine
                .getPlaybackPulse()
                .timeSignature
                .numerator,

            9
        )

        engine.randomizerPulse.pattern = [
            {
                sound: 'accent',
                groupStart: true,
            },
            {
                sound: 'click',
                groupStart: false,
            },
            {
                sound: 'click',
                groupStart: false,
            },
            {
                sound: 'accent',
                groupStart: true,
            },
        ]

        assert.equal(
            engine.getPlaybackGroupSizeForBeat(
                4,
                engine.randomizerPulse
            ),
            3
        )
    }
)

test(
    'creative tools keep separate playback modes',
    () => {
        const engine = audioEngine()

        engine.creativePlaybackMode = 'click'
        engine.randomizerPlaybackMode = 'pulse'

        engine.creativeMode = 'randomizer'

        assert.equal(
            engine.getCreativePlaybackMode(),
            'pulse'
        )

        engine.creativeMode = 'pulse-editor'

        assert.equal(
            engine.getCreativePlaybackMode(),
            'click'
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

test('click sounds begin loading in parallel', async () => {
    const engine = audioEngine()

    const pending = [
        createDeferredPromise(),
        createDeferredPromise(),
        createDeferredPromise(),
    ]

    const requested = []

    engine.currentDawProfile = {
        click: '/click.wav',
        accent: '/accent.wav',
        finish: '/finish.wav',
    }

    engine.loadAudioBuffer = path => {
        requested.push(path)
        return pending[requested.length - 1].promise
    }

    const loading = engine.loadClickSounds()

    assert.deepEqual(requested, [
        '/click.wav',
        '/accent.wav',
        '/finish.wav',
    ])

    pending.forEach(item => item.resolve({}))
    await loading
})

test('shared audio files are loaded only once', async () => {
    const engine = audioEngine()
    const requested = []

    const accentBuffer = { name: 'accent' }

    engine.currentDawProfile = {
        click: '/click.wav',
        accent: '/accent.wav',
        finish: '/accent.wav',
    }

    engine.loadAudioBuffer = async path => {
        requested.push(path)

        return path === '/accent.wav'
            ? accentBuffer
            : { name: 'click' }
    }

    await engine.loadClickSounds()

    assert.deepEqual(requested, [
        '/click.wav',
        '/accent.wav',
    ])

    assert.strictEqual(
        engine.finishBuffer,
        engine.accentBuffer
    )
})

test('an older profile load cannot overwrite a newer profile', async () => {
    const engine = audioEngine()
    const oldLoading = createDeferredPromise()

    engine.currentDawProfile = {
        click: '/old-click.wav',
        accent: '/old-accent.wav',
        finish: '/old-accent.wav',
    }

    engine.loadAudioBuffer = path => {
        if (path.startsWith('/old-')) {
            return oldLoading.promise.then(() => ({ path }))
        }

        return Promise.resolve({ path })
    }

    const firstLoad = engine.loadClickSounds()

    engine.currentDawProfile = {
        click: '/new-click.wav',
        accent: '/new-accent.wav',
        finish: '/new-accent.wav',
    }

    const secondLoad = engine.loadClickSounds()

    await secondLoad

    oldLoading.resolve()
    await firstLoad

    assert.equal(engine.clickBuffer.path, '/new-click.wav')
    assert.equal(engine.accentBuffer.path, '/new-accent.wav')
    assert.equal(engine.finishBuffer.path, '/new-accent.wav')
})

test('concurrent sound loads share the same request', async () => {
    const engine = audioEngine()
    const pending = createDeferredPromise()
    const requested = []

    engine.currentDawProfile = {
        click: '/click.wav',
        accent: '/accent.wav',
        finish: '/accent.wav',
    }

    engine.loadAudioBuffer = path => {
        requested.push(path)
        return pending.promise.then(() => ({ path }))
    }

    const first = engine.loadClickSounds()
    const second = engine.loadClickSounds()

    assert.deepEqual(requested, [
        '/click.wav',
        '/accent.wav',
    ])

    pending.resolve()

    await Promise.all([first, second])
})

test('failed sound loading can be retried', async () => {
    const engine = audioEngine()
    let attempts = 0

    engine.currentDawProfile = {
        click: '/click.wav',
        accent: '/accent.wav',
        finish: '/accent.wav',
    }

    engine.loadAudioBuffer = async path => {
        attempts++

        if (attempts <= 2) {
            throw new Error('Network error')
        }

        return { path }
    }

    await assert.rejects(
        engine.loadClickSounds(),
        /Network error/
    )

    await engine.loadClickSounds()

    assert.equal(engine.clickBuffer.path, '/click.wav')
    assert.equal(engine.accentBuffer.path, '/accent.wav')
    assert.equal(engine.soundLoadingPromise, null)
})

test('stale loading cannot overwrite the latest profile request', async () => {
    const engine = audioEngine()

    const ableton = {
        click: '/ableton/click.wav',
        accent: '/ableton/accent.wav',
        finish: '/ableton/accent.wav',
    }

    const cubase = {
        click: '/cubase/click.wav',
        accent: '/cubase/accent.wav',
        finish: '/cubase/accent.wav',
    }

    const first = createDeferredPromise()
    const second = createDeferredPromise()
    const third = createDeferredPromise()

    let pending = first

    engine.loadAudioBuffer = path => {
        const request = pending

        return request.promise.then(() => ({
            path,
            generation: pendingGeneration.get(request),
        }))
    }

    const pendingGeneration = new Map([
        [first, 1],
        [second, 2],
        [third, 3],
    ])

    engine.currentDawProfile = ableton
    const firstLoad = engine.loadClickSounds()

    pending = second
    engine.currentDawProfile = cubase
    const secondLoad = engine.loadClickSounds()

    pending = third
    engine.currentDawProfile = ableton
    const thirdLoad = engine.loadClickSounds()

    third.resolve()
    await thirdLoad

    second.resolve()
    await secondLoad

    first.resolve()
    await firstLoad

    assert.equal(engine.clickBuffer.generation, 3)
    assert.equal(engine.accentBuffer.generation, 3)
    assert.equal(engine.finishBuffer.generation, 3)
})