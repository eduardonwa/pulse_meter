let alphaTabModule = null
let activeApi = null

const instances = new WeakMap()
const mountPromises = new WeakMap()

const readyPromises = new WeakMap()
const renderedBpms = new WeakMap()

async function getAlphaTab() {
    if (!alphaTabModule) {
        const alphaTabUrl = `${window.location.origin}/alphatab/alphaTab.mjs`

        alphaTabModule = await import(/* @vite-ignore */ alphaTabUrl)
    }

    return alphaTabModule
}

function decodeBase64(value) {
    const binary = atob(value)
    const bytes = Uint8Array.from(binary, character => character.charCodeAt(0))
    return new TextDecoder().decode(bytes)
}

export function dispatchPlaybackState(
    element,
    state,
    playingState
) {
    const playing = state === playingState

    window.dispatchEvent(
        new CustomEvent('alphatab:playback-state', {
            detail: {
                element,
                playing,
            },
        })
    )
}

async function mountAlphaTab(element) {
    if (!element) return null

    if (instances.has(element)) {
        await readyPromises.get(element)
        return instances.get(element)
    }

    if (mountPromises.has(element)) {
        return await mountPromises.get(element)
    }

    const mountPromise = (async () => {
        const encodedAlphaTex = element.dataset.alphaTex
        const bpm = Number(element.dataset.bpm ?? 100)

        if (!encodedAlphaTex) return null

        const alphaTab = await getAlphaTab()
        const pattern = decodeBase64(encodedAlphaTex)

        const api = new alphaTab.AlphaTabApi(element, {
            core: {
                fontDirectory: '/font/',
            },

            display: {
                staveProfile: 'Tab',
            },

            player: {
                playerMode: alphaTab.PlayerMode.EnabledSynthesizer,
                soundFont: '/soundfont/sonivox.sf2',

                enableUserInteraction: true,
                enableCursor: true,
                enableElementHighlighting: true,
            },
        })

        api.metronomeVolume = 1

        api.playerStateChanged.on(args => {
            dispatchPlaybackState(
                element,
                args.state,
                alphaTab.synth.PlayerState.Playing
            )
        })

        const readyPromise = new Promise(resolve => {
            api.playerReady.on(() => {
                resolve()
            })
        })

        api.tex(`
\\tempo ${bpm}

${pattern}
        `)

        instances.set(element, api)
        readyPromises.set(element, readyPromise)
        renderedBpms.set(element, bpm)

        await readyPromise

        return api
    })()

    mountPromises.set(element, mountPromise)

    try {
        return await mountPromise
    } finally {
        mountPromises.delete(element)
    }
}

window.addEventListener('alphatab:mount', async event => {
    await mountAlphaTab(event.detail?.element)
})

window.addEventListener('alphatab:play-pause', async event => {
    const api = await mountAlphaTab(event.detail?.element)

    if (!api) return

    if (activeApi && activeApi !== api) {
        activeApi.stop()
    }

    activeApi = api
    api.playPause()
})

window.addEventListener('alphatab:start-exercise', async event => {
    const index = Number(event.detail?.index)
    const shouldLoop = Boolean(event.detail?.loop)

    const element = document.querySelector(
        `[data-alphatab][data-exercise-index="${index}"]`
    )

    if (!element) return

    const api = await mountAlphaTab(element)

    if (!api) return

    if (activeApi && activeApi !== api) {
        activeApi.stop()
    }

    activeApi = api
    api.isLooping = shouldLoop

    api.play()
})

window.addEventListener('alphatab:stop', () => {
    activeApi?.stop()
    activeApi = null
})

window.addEventListener('alphatab:toggle-loop', async event => {
    const api = await mountAlphaTab(event.detail?.element)

    if (!api) return

    api.isLooping = !api.isLooping
})

window.addEventListener('alphatab:set-loop', event => {
    if (!activeApi) return

    activeApi.isLooping = Boolean(event.detail?.enabled)
})

window.addEventListener('alphatab:set-bpm', async event => {
    const index = Number(event.detail?.index)
    const bpm = Number(event.detail?.bpm)

    if (!Number.isFinite(bpm) || bpm <= 0) return

    const element = document.querySelector(
        `[data-alphatab][data-exercise-index="${index}"]`
    )

    if (!element) return

    const api = await mountAlphaTab(element)
    const renderedBpm = renderedBpms.get(element)

    if (!api || !renderedBpm) return

    api.playbackSpeed = bpm / renderedBpm
})
