import { isAlphaTabCountInEnabled } from './metronome/alphatab/count-in-preference.js'
import { playbackAlphaTex } from './metronome/alphatab/tab-editor.js'
let alphaTabModule = null
let activeApi = null

const instances = new WeakMap()
const mountPromises = new WeakMap()

const readyPromises = new WeakMap()
const renderedBpms = new WeakMap()
const playbackRequests = new WeakMap()
const routineLoopModes = new WeakMap()
// Editor previews have independent, per-element transport controls.
// Routine/practice preference functions remain unchanged.
const editorPreviewOptions = new WeakMap()
const editorCountInActive = new WeakMap()
const pendingEditorSeeks = new WeakMap()

function applyEditorPreviewOptions(element, api) {
    const options = editorPreviewOptions.get(element)
    if (!options || !api) return false
    api.countInVolume = options.countIn ? 1 : 0
    api.metronomeVolume = options.metronome ? 1 : 0
    api.isLooping = options.loop && !options.countIn
    return true
}
let playbackGeneration = 0

// Native alphaTab looping jumps straight to the first note. To repeat the
// lead-in, finish each pass, then start playback again as a new pass.
export function setExerciseLoopMode(api, enabled, countInEnabled = isAlphaTabCountInEnabled()) {
    routineLoopModes.set(api, Boolean(enabled))
    api.isLooping = Boolean(enabled) && !countInEnabled
}

export function restartExerciseWithCountIn(api, { isActive, schedule = callback => setTimeout(callback, 0) }) {
    if (!routineLoopModes.get(api) || !isAlphaTabCountInEnabled() || !isActive()) return false
    const request = playbackGeneration
    schedule(() => {
        if (playbackGeneration !== request || !isActive() || !routineLoopModes.get(api) || !isAlphaTabCountInEnabled()) return
        api.stop()
        configureCountIn(api)
        api.play()
    })
    return true
}

function configureCountIn(api) {
    api.countInVolume = isAlphaTabCountInEnabled() ? 1 : 0
}

async function getAlphaTab() {
    if (!alphaTabModule) {
        const alphaTabUrl = `${window.location.origin}/alphatab/alphaTab.mjs`

        alphaTabModule = await import(/* @vite-ignore */ alphaTabUrl)
    }

    return alphaTabModule
}

// The exercise BPM is the base tempo for native MusicXML scores. Relative
// tempo changes remain in the score and playbackSpeed scales them together.
function musicXmlFirstTempo(xml, fallback) {
    const match = xml?.match(/<sound\b[^>]*\btempo\s*=\s*["'](\d+(?:\.\d+)?)["']/i)
    const tempo = Number(match?.[1])
    return Number.isFinite(tempo) && tempo > 0 ? tempo : fallback
}

function decodeBase64(value) {
    const binary = atob(value)
    const bytes = Uint8Array.from(binary, character => character.charCodeAt(0))
    return new TextDecoder().decode(bytes)
}

function escapeDirectiveString(value) {
    return String(value)
        .replaceAll('\\', '\\\\')
        .replaceAll('"', '\\"')
}

function applyMetadata(alphaTex, metadata) {
    let pattern = String(alphaTex)
        .replace(/\\tempo\s+\d+(?:\.\d+)?/gi, '')

    const directives = []

    if (Object.hasOwn(metadata, 'title')) {
        pattern = pattern.replace(/^\s*\\title\b[^\r\n]*(?:\r?\n|$)/gmi, '')

        if (metadata.title) {
            directives.push(`\\title "${escapeDirectiveString(metadata.title)}"`)
        }
    }

    if (Object.hasOwn(metadata, 'track')) {
        pattern = pattern.replace(/^\s*\\track\b[^\r\n]*(?:\r?\n|$)/gmi, '')

        if (metadata.track) {
            directives.push(`\\track "${escapeDirectiveString(metadata.track)}"`)
        }
    }

    if (Object.hasOwn(metadata, 'instrument')) {
        pattern = pattern.replace(/^\s*\\instrument\b[^\r\n]*(?:\r?\n|$)/gmi, '')

        if (Number.isInteger(metadata.instrument) && metadata.instrument >= 0) {
            directives.push(`\\instrument ${metadata.instrument}`)
        }
    }

    directives.push(`\\tempo ${metadata.bpm}`)

    return `${directives.join('\n')}\n\n${pattern.trim()}`
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

// Resolve a seek position against the same MIDI beat map used by alphaTab.
// Unlike elapsed-time estimates, this respects rests, dots and tuplets.
function dispatchBeatAtTick(api, element, tick) {
    if (!Number.isFinite(tick) || tick < 0) return
    const beat = api.tickCache?.findBeat(new Set([0]), tick)?.currentBeat
    if (!beat) return
    window.dispatchEvent(new CustomEvent('alphatab:played-beat', {
        detail: { element, measureIndex: beat.voice?.bar?.index, beatIndex: beat.index },
    }))
}

function scoreScrollContainer(element) {
    return element.closest('.mobile-exercise-view-dialog__content') ?? 'html,body'
}

function syncScoreScrollContainer(api, element) {
    const container = scoreScrollContainer(element)

    if (api.settings.player.scrollElement === container) return

    api.settings.player.scrollElement = container
    api.updateSettings()
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
        const originalMusicXml = element.dataset.musicxml
        const bpm = Number(element.dataset.bpm ?? 100)
        const isAdminPreview = element.dataset.adminPreview === 'true'

        if (!encodedAlphaTex && !originalMusicXml) return null

        const alphaTab = await getAlphaTab()
        const pattern = encodedAlphaTex ? decodeBase64(encodedAlphaTex) : null

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

                enableUserInteraction: !isAdminPreview,
                enableCursor: true,
                enableAnimatedBeatCursor: true,
                enableElementHighlighting: !isAdminPreview,
                scrollElement: scoreScrollContainer(element),

                scrollMode: isAdminPreview
                    ? alphaTab.ScrollMode.Off
                    : alphaTab.ScrollMode.Continuous,
            },
        })

        api.renderFinished.on(() => {
            element.dataset.alphaTabRendered = 'true'
            window.dispatchEvent(new CustomEvent('alphatab:rendered', {
                detail: { element },
            }))
        })

        api.metronomeVolume = 1
        configureCountIn(api)
        applyEditorPreviewOptions(element, api)
        api.playerFinished.on(() => {
            const editorOptions = editorPreviewOptions.get(element)
            if (editorOptions?.loop && editorOptions.countIn && activeApi === api) {
                const generation = playbackGeneration
                // Native looping skips count-in. Restart a new pass to include it.
                setTimeout(() => {
                    if (generation !== playbackGeneration || activeApi !== api ||
                        !editorPreviewOptions.get(element)?.loop || !editorPreviewOptions.get(element)?.countIn) return
                    api.stop()
                    applyEditorPreviewOptions(element, api)
                    api.play()
                }, 0)
                return
            }
            if (!editorOptions?.loop) {
                window.dispatchEvent(new CustomEvent('alphatab:playback-finished', {
                    detail: { element },
                }))
            }
            if (!editorOptions) restartExerciseWithCountIn(api, { isActive: () => activeApi === api })
        })
        // Editor UI has its own grid, so forward the actual alphaTab beat
        // rather than estimating note positions from elapsed milliseconds.
        api.playedBeatChanged.on(beat => {
            window.dispatchEvent(new CustomEvent('alphatab:played-beat', {
                detail: {
                    element,
                    measureIndex: beat?.voice?.bar?.index,
                    beatIndex: beat?.index,
                },
            }))
        })

        let waitingForScoreStart = false
        api.playerPositionChanged.on(args => {
            if (editorPreviewOptions.has(element)) editorCountInActive.set(api, args.currentTick < 0)
            const pendingSeek = pendingEditorSeeks.get(api)
            if (pendingSeek && args.currentTick >= 0 && activeApi === api) {
                pendingEditorSeeks.delete(api)
                // Let alphaTab complete the count-in transition before seeking.
                // Guard the queued callback against stop/new-preview operations.
                setTimeout(() => {
                    if (playbackGeneration !== pendingSeek.generation || activeApi !== api) return
                    api.timePosition = pendingSeek.position
                }, 0)
                return
            }
            if (pendingSeek) return // Keep the slider at the selected destination during count-in.
            window.dispatchEvent(new CustomEvent('alphatab:playback-position', {
                detail: { element, currentTime: args.currentTime, endTime: args.endTime },
            }))
            if (args.isSeek) dispatchBeatAtTick(api, element, args.currentTick)
            if (!waitingForScoreStart || args.currentTick < 0) return
            waitingForScoreStart = false
            window.dispatchEvent(new CustomEvent('alphatab:score-started', {
                detail: { element },
            }))
        })

        api.playerStateChanged.on(args => {
            waitingForScoreStart = args.state === alphaTab.synth.PlayerState.Playing
            dispatchPlaybackState(
                element,
                args.state,
                alphaTab.synth.PlayerState.Playing
            )
        })

        const readyPromise = new Promise((resolve, reject) => {
            api.playerReady.on(() => {
                window.dispatchEvent(new CustomEvent('alphatab:playback-position', {
                    detail: { element, currentTime: api.timePosition, endTime: api.endTime },
                }))
                resolve()
            })
            api.error.on(error => {
                window.dispatchEvent(new CustomEvent('alphatab:load-error', {
                    detail: { element, message: error?.message ?? 'alphaTab could not load this score' },
                }))
                reject(error)
            })
        })

        if (originalMusicXml) {
            // Load the complete source into alphaTab's native MusicXML importer.
            // Do not pass it through the limited Dorelog editor or alphaTex.
            api.load(new TextEncoder().encode(originalMusicXml))
        } else {
            api.tex(applyMetadata(playbackAlphaTex(pattern), { bpm }))
        }

        instances.set(element, api)
        readyPromises.set(element, readyPromise)
        renderedBpms.set(element, originalMusicXml ? musicXmlFirstTempo(originalMusicXml, bpm) : bpm)

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


function encodeBase64(value) {
    const bytes = new TextEncoder().encode(value)

    let binary = ''

    bytes.forEach(byte => {
        binary += String.fromCharCode(byte)
    })

    return btoa(binary)
}

window.addEventListener('alphatab:update', async event => {
    const element = event.detail?.element
    const alphaTex = String(
        event.detail?.alphaTex ?? ''
    ).trim()

    const bpm = Number(event.detail?.bpm ?? 80)
    const metadata = {
        bpm,
        title: String(event.detail?.title ?? '').trim(),
        track: String(event.detail?.track ?? '').trim(),
        instrument: Number(event.detail?.instrument ?? (/\\instrument\s+(\d+)/.exec(alphaTex)?.[1] ?? 25)),
    }

    if (!element || !alphaTex) return

    const renderedAlphaTex = applyMetadata(playbackAlphaTex(alphaTex), metadata)

    element.dataset.alphaTex = encodeBase64(alphaTex)
    element.dataset.bpm = String(bpm)

    const existingApi = instances.get(element)

    if (!existingApi) {
        await mountAlphaTab(element)
        return
    }

    existingApi.stop()

    existingApi.playbackSpeed = 1
    existingApi.tex(renderedAlphaTex)

    renderedBpms.set(element, bpm)
})

window.addEventListener('alphatab:preview-draft', async event => {
    const { element, alphaTex, musicXml, bpm, play, instrument, transportOptions } = event.detail ?? {}
    if (!element) return

    if (!play) {
        playbackRequests.set(element, ++playbackGeneration)
        const api = instances.get(element)
        if (api) pendingEditorSeeks.delete(api)
        api?.stop()
        if (activeApi === api) activeApi = null
        return
    }
    if (transportOptions) editorPreviewOptions.set(element, {
        loop: Boolean(transportOptions.loop),
        countIn: Boolean(transportOptions.countIn),
        metronome: Boolean(transportOptions.metronome),
    })

    if (!alphaTex && !musicXml) return

    const request = ++playbackGeneration
    playbackRequests.set(element, request)
    const pattern = alphaTex
        ? applyMetadata(playbackAlphaTex(alphaTex), { bpm: Number(bpm) || 100, instrument: Number(instrument ?? 25) })
        : null
    if (musicXml) {
        element.dataset.musicxml = musicXml
        delete element.dataset.alphaTex
    } else {
        element.dataset.alphaTex = encodeBase64(alphaTex)
        delete element.dataset.musicxml
    }
    element.dataset.bpm = String(Number(bpm) || 100)

    // Mount if needed, then replace the score on subsequent previews.
    const alreadyMounted = instances.has(element)
    let api
    try {
        api = await mountAlphaTab(element)
    } catch (error) {
        console.error('alphaTab could not preview the imported score:', error)
        return
    }
    if (!api || !element.isConnected || playbackRequests.get(element) !== request || playbackGeneration !== request) return

    if (activeApi && activeApi !== api) activeApi.stop()
    pendingEditorSeeks.delete(api)
    activeApi = api
    if (alreadyMounted) {
        api.stop()
        if (musicXml) {
            // Native file loading is asynchronous. Never play the previous
            // score while alphaTab is still importing the new MusicXML.
            try {
                await new Promise((resolve, reject) => {
                    let offLoaded
                    let offError
                    const cleanup = () => { offLoaded?.(); offError?.() }
                    offLoaded = api.midiLoaded.on(() => { cleanup(); resolve() })
                    offError = api.error.on(error => { cleanup(); reject(error) })
                    if (!api.load(new TextEncoder().encode(musicXml))) {
                        cleanup()
                        reject(new Error('alphaTab did not accept this MusicXML file'))
                    }
                })
            } catch (error) {
                console.error('alphaTab could not load the imported score:', error)
                return
            }
        } else api.tex(pattern)
    }
    if (playbackRequests.get(element) !== request || playbackGeneration !== request) return
    api.playbackSpeed = musicXml ? Number(bpm) / musicXmlFirstTempo(musicXml, Number(bpm) || 100) : 1
    if (!applyEditorPreviewOptions(element, api)) {
        setExerciseLoopMode(api, false)
        configureCountIn(api)
    }
    // A slider position chosen before the first Play must also affect audio.
    const seekPercent = Number(event.detail?.seekPercent)
    if (Number.isFinite(seekPercent) && seekPercent > 0 && Number.isFinite(api.endTime) && api.endTime > 0) {
        api.timePosition = Math.min(100, Math.max(0, seekPercent)) / 100 * api.endTime
    }
    api.play()
})

window.addEventListener('alphatab:mount', async event => {
    await mountAlphaTab(event.detail?.element)
})

function notifyAudioPreparation(index, loading) {
    window.dispatchEvent(new CustomEvent('alphatab:loading-state', {
        detail: { index, loading },
    }))
}

window.addEventListener('alphatab:play-pause', async event => {
    const element = event.detail?.element
    const index = Number(element?.dataset.exerciseIndex)
    const request = ++playbackGeneration
    notifyAudioPreparation(index, true)

    try {
        const api = await mountAlphaTab(element)
        if (!api || playbackGeneration !== request) return

        if (activeApi && activeApi !== api) activeApi.stop()
        activeApi = api
        // Routine viewers remain single-listen previews; the editor instead
        // preserves its own transport options when pausing and resuming.
        if (!applyEditorPreviewOptions(element, api)) {
            setExerciseLoopMode(api, false)
            configureCountIn(api)
        }
        api.playPause()
    } catch (error) {
        console.error('AlphaTab preview could not start:', error)
    } finally {
        if (playbackGeneration === request) notifyAudioPreparation(index, false)
    }
})

window.addEventListener('alphatab:start-exercise', async event => {
    const index = Number(event.detail?.index)
    const element = document.querySelector(
        `[data-alphatab][data-exercise-index="${index}"]`
    )
    if (!element) {
        window.dispatchEvent(new CustomEvent('alphatab:playback-error', {
            detail: { index },
        }))
        return
    }

    const request = ++playbackGeneration
    notifyAudioPreparation(index, true)

    try {
        const api = await mountAlphaTab(element)
        if (playbackGeneration !== request) return
        if (!api) {
            window.dispatchEvent(new CustomEvent('alphatab:playback-error', {
                detail: { index },
            }))
            return
        }

        if (activeApi && activeApi !== api) activeApi.stop()
        activeApi = api
        // If count-in is enabled, restart each completed pass with a new
        // count-in instead of alphaTab's seamless native loop.
        setExerciseLoopMode(api, true)
        configureCountIn(api)
        api.play()
    } catch (error) {
        console.error('AlphaTab exercise could not start:', error)
        if (playbackGeneration === request) {
            window.dispatchEvent(new CustomEvent('alphatab:playback-error', {
                detail: { index },
            }))
        }
    } finally {
        if (playbackGeneration === request) notifyAudioPreparation(index, false)
    }
})

window.addEventListener('alphatab:stop', () => {
    notifyAudioPreparation(null, false)
    ++playbackGeneration
    if (activeApi) {
        pendingEditorSeeks.delete(activeApi)
        setExerciseLoopMode(activeApi, false)
    }
    activeApi?.stop()
    activeApi = null
})

window.addEventListener('alphatab:toggle-loop', async event => {
    const api = await mountAlphaTab(event.detail?.element)

    if (!api) return

    setExerciseLoopMode(api, Boolean(event.detail?.enabled))
})

window.addEventListener('alphatab:count-in-changed', () => {
    if (!activeApi) return
    configureCountIn(activeApi)
    // Switching the setting during a routine also switches between
    // counted restarts and native seamless looping at the next ending.
    activeApi.isLooping = Boolean(routineLoopModes.get(activeApi)) && !isAlphaTabCountInEnabled()
})

window.addEventListener('alphatab:set-bpm', async event => {
    const index = Number(event.detail?.index)
    const bpm = Number(event.detail?.bpm)

    if (!Number.isFinite(bpm) || bpm <= 0) return

    const element = document.querySelector(
        `[data-alphatab][data-exercise-index="${index}"]`
    )

    if (!element) return

    element.dataset.bpm = String(bpm)

    const api = instances.get(element)

    // Keep closed exercises lazy. Their updated data-bpm will be
    // used the next time AlphaTab is mounted.
    if (!api) return

    if (event.detail?.renderTempo && !element.dataset.musicxml) {
        const encodedAlphaTex = element.dataset.alphaTex
        if (!encodedAlphaTex) return

        api.stop()
        api.playbackSpeed = 1
        api.tex(applyMetadata(playbackAlphaTex(decodeBase64(encodedAlphaTex)), { bpm }))
        renderedBpms.set(element, bpm)
        return
    }

    // MusicXML retains its native tempo map. Scale playback instead of
    // re-importing the score or discarding its internal tempo changes.

    const renderedBpm = renderedBpms.get(element)

    if (!renderedBpm) return

    api.playbackSpeed = bpm / renderedBpm
})

window.addEventListener('alphatab:render', event => {
    const element = event.detail?.element

    if (!element) return

    const api = instances.get(element)

    if (!api) return

    syncScoreScrollContainer(api, element)
    api.render()
})

window.addEventListener('alphatab:seek', event => {
    const element = event.detail?.element
    const api = instances.get(element)
    const percent = Number(event.detail?.percent)
    if (!api || !Number.isFinite(percent) || !Number.isFinite(api.endTime) || api.endTime <= 0) return
    const position = Math.max(0, Math.min(100, percent)) / 100 * api.endTime
    // Do not stop, seek or restart the synth while negative count-in ticks
    // are playing: that can strand playback or produce a stray MIDI note.
    if (editorPreviewOptions.has(element) && editorCountInActive.get(api) && activeApi === api) {
        pendingEditorSeeks.set(api, { position, generation: playbackGeneration })
        return
    }
    pendingEditorSeeks.delete(api)
    api.timePosition = position
    // The alphaTab position event refreshes the exact beat even while paused.
})

// Update editor transport controls without changing Practice preferences.
window.addEventListener('alphatab:editor-transport-options', event => {
    const { element, options } = event.detail ?? {}
    if (!element || !options) return
    editorPreviewOptions.set(element, {
        loop: Boolean(options.loop),
        countIn: Boolean(options.countIn),
        metronome: Boolean(options.metronome),
    })
    applyEditorPreviewOptions(element, instances.get(element))
})
