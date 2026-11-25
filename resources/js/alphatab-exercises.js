import { isAlphaTabCountInEnabled } from './metronome/alphatab/count-in-preference.js'
import { editorPreviewAlphaTex } from './metronome/alphatab/tab-editor.js'
import { installMidiReloadListener, loadReplacementMusicXml } from './metronome/alphatab/midi-reload.js'
import { fixAlphaTabMidiInfoGetter } from './metronome/alphatab/fix-midi-info-getter.js'
let alphaTabModule = null
let activeApi = null

const instances = new WeakMap()
const mountPromises = new WeakMap()

const readyPromises = new WeakMap()
const renderedBpms = new WeakMap()
const playbackRequests = new WeakMap()
const editorScoreCursors = new WeakMap()

// AlphaTex/Dorelog number strings from the top; AlphaTab's Note.string
// numbers them from the bottom. Convert only at the renderer boundary.
function editorStringFromAlphaTab(value) {
    return Number.isInteger(value) && value >= 1 && value <= 6 ? 7 - value : null
}

// AlphaTab 1.8 paints TAB staff lines as filled SVG rectangles, split
// around fret numbers. Read their actual centers once per rendered SVG.
const editorStaffLines = new WeakMap()

function editorScoreStaffLines(svg, color) {
    if (editorStaffLines.has(svg)) return editorStaffLines.get(svg)
    const lines = new Set()
    for (const rect of svg.querySelectorAll('rect[fill]')) {
        if (rect.getAttribute('fill').toLowerCase() !== color) continue
        const width = Number(rect.getAttribute('width'))
        const height = Number(rect.getAttribute('height'))
        const y = Number(rect.getAttribute('y'))
        if (height > 0 && width > height && Number.isFinite(y)) {
            lines.add(y + height / 2)
        }
    }
    const result = [...lines].sort((a, b) => a - b)
    // Editable scores contain a single six-string TAB staff per system.
    // Never invent a line if the SVG does not expose all six.
    const geometry = result.length === 6 ? result : null
    editorStaffLines.set(svg, geometry)
    return geometry
}

// Resolve the same SVG geometry for editing, range highlights and pointer hits.
function editorScoreGeometry(element, api) {
    const surface = element.querySelector('.at-surface')
    if (!surface || !api?.boundsLookup) return null
    const origin = surface.getBoundingClientRect()
    const host = element.getBoundingClientRect()
    const color = api.settings.display.resources.staffLineColor.rgba.toLowerCase()
    const systems = []
    for (const svg of surface.querySelectorAll('svg')) {
        const lines = editorScoreStaffLines(svg, color)
        const matrix = svg.getScreenCTM()
        if (!lines || !matrix) continue
        systems.push({
            box: svg.getBoundingClientRect(),
            lines: lines.map(y => new DOMPoint(0, y).matrixTransform(matrix).y),
        })
    }
    return {
        origin, systems,
        left: x => x - host.left - element.clientLeft + element.scrollLeft,
        top: y => y - host.top - element.clientTop + element.scrollTop,
        systemAt: (x, y) => systems.find(({ box }) =>
            x >= box.left && x <= box.right && y >= box.top && y <= box.bottom),
    }
}

// Keep editing inside the dialog's visible score area, above its sticky dock.
function revealEditorScorePosition(element, target) {
    if (!target || element.dataset.scoreEditable !== 'true' ||
        element.classList.contains('is-playing')) return
    const dialog = element.closest('.tab-editor-dialog')
    if (!dialog?.open) return
    const rect = target.getBoundingClientRect()
    const host = element.getBoundingClientRect()
    const dock = dialog.querySelector('.tab-editor__bottom-dock')?.getBoundingClientRect()
    const viewport = dialog.getBoundingClientRect()
    const top = viewport.top + dialog.clientTop
    const bottom = Math.min(top + dialog.clientHeight, dock?.top ?? Infinity)
    const margin = 12
    if (rect.top < top + margin) dialog.scrollTop += rect.top - top - margin
    else if (rect.bottom > bottom - margin) dialog.scrollTop += rect.bottom - bottom + margin
    const left = host.left + element.clientLeft
    const right = left + element.clientWidth
    if (rect.left < left + margin) element.scrollLeft += rect.left - left - margin
    else if (rect.right > right - margin) element.scrollLeft += rect.right - right + margin
}

function updateEditorScoreCaret(element, api) {
    const selection = editorScoreCursors.get(element)
    let caret = element.querySelector(':scope > .tab-editor-score-caret')
    const range = element.querySelector(':scope > .tab-editor-score-range')
    range?.remove()
    if (!selection || !api?.score || !api.boundsLookup) {
        caret?.remove()
        return
    }
    const geometry = editorScoreGeometry(element, api)
    if (!geometry) {
        caret?.remove()
        return
    }
    const { origin, systemAt, left, top } = geometry
    const bars = api.score.tracks?.[0]?.staves?.[0]?.bars
    const boundsAt = (measureIndex, beatIndex) => {
        const beat = bars?.[measureIndex]?.voices?.[0]?.beats?.[beatIndex]
        return beat && api.boundsLookup.findBeat(beat)
    }
    if (selection.range?.length > 1) {
        caret?.remove()
        const overlay = document.createElement('div')
        overlay.className = 'tab-editor-score-range'
        overlay.setAttribute('aria-hidden', 'true')
        const fragment = document.createDocumentFragment()
        let activeMark = null
        for (const position of selection.range) {
            const bounds = boundsAt(position.measureIndex, position.beatIndex)
            if (!bounds) continue
            const rect = bounds.realBounds
            const system = systemAt(origin.left + bounds.onNotesX, origin.top + rect.y + rect.h / 2)
            if (!system) continue
            const lines = system.lines
            const spacing = (lines[5] - lines[0]) / 5
            const mark = document.createElement('div')
            mark.className = 'tab-editor-score-range-beat'
            mark.style.left = `${left(origin.left + rect.x)}px`
            mark.style.top = `${top(lines[0] - spacing / 2)}px`
            mark.style.width = `${rect.w}px`
            mark.style.height = `${lines[5] - lines[0] + spacing}px`
            fragment.appendChild(mark)
            if (position.measureIndex === selection.measureIndex && position.beatIndex === selection.beatIndex) activeMark = mark
        }
        overlay.appendChild(fragment)
        element.appendChild(overlay)
        revealEditorScorePosition(element, activeMark)
        return
    }
    const { measureIndex, beatIndex, insertion, after, newMeasure } = selection
    const bounds = boundsAt(measureIndex, beatIndex)
    const selectedString = Number(selection.string)
    if (newMeasure || !bounds || !Number.isInteger(selectedString) ||
        selectedString < 1 || selectedString > 6) {
        caret?.remove()
        return
    }
    const head = bounds.notes?.find(item =>
        editorStringFromAlphaTab(item.note?.string) === selectedString)?.noteHeadBounds
        ?? bounds.notes?.[0]?.noteHeadBounds
    let beatX = head ? head.x + head.w / 2 : bounds.onNotesX
    if (insertion) {
        const barBounds = bounds.barBounds
        const neighbor = barBounds.beats[beatIndex + (after ? 1 : -1)]
        const edge = after
            ? (neighbor?.onNotesX ?? barBounds.realBounds.x + barBounds.realBounds.w)
            : (neighbor?.onNotesX ?? bounds.realBounds.x)
        beatX = (bounds.onNotesX + edge) / 2
    }
    const screenX = origin.left + beatX
    const system = systemAt(screenX, origin.top + bounds.realBounds.y + bounds.realBounds.h / 2)
    const centerY = system?.lines[selectedString - 1]
    if (!Number.isFinite(screenX) || !Number.isFinite(centerY)) {
        caret?.remove()
        return
    }
    if (!caret) {
        caret = document.createElement('div')
        caret.className = 'tab-editor-score-caret'
        caret.setAttribute('aria-hidden', 'true')
        element.appendChild(caret)
    }
    caret.classList.toggle('is-insertion', Boolean(insertion))
    caret.style.left = `${left(screenX)}px`
    caret.style.top = `${top(centerY)}px`
    caret.style.width = '23px'
    caret.style.height = '21px'
    revealEditorScorePosition(element, caret)
}

// Use one pointer path for both notes and empty strings. AlphaTab's separate
// beat/note mouse events must not both toggle the same selection.
function installEditorScoreSelection(element, api) {
    let dragBeat = null
    const hit = event => {
        if (element.dataset.scoreEditable !== 'true' || element.classList.contains('is-playing')) return null
        const geometry = editorScoreGeometry(element, api)
        if (!geometry) return null
        const { clientX, clientY } = event
        const system = geometry.systemAt(clientX, clientY)
        if (!system) return null
        const lines = system.lines
        const spacing = (lines[5] - lines[0]) / 5
        if (clientY < lines[0] - spacing / 2 || clientY > lines[5] + spacing / 2) return null
        const beat = api.boundsLookup.getBeatAtPos(clientX - geometry.origin.left, clientY - geometry.origin.top)
        if (!beat) return null
        const string = lines.reduce((nearest, y, i) =>
            Math.abs(y - clientY) < Math.abs(lines[nearest] - clientY) ? i : nearest, 0) + 1
        return { beat, string }
    }
    const dispatch = ({ beat, string }, event, dragging = false) => {
        window.dispatchEvent(new CustomEvent('alphatab:editor-select', {
            detail: {
                element,
                measureIndex: beat.voice?.bar?.masterBar?.index ?? beat.voice?.bar?.index,
                beatIndex: beat.index,
                string,
                shiftKey: dragging || event.shiftKey,
                ctrlKey: !dragging && event.ctrlKey,
                metaKey: !dragging && event.metaKey,
            },
        }))
    }
    element.addEventListener('pointerdown', event => {
        dragBeat = null
        if (event.button !== 0) return
        const target = hit(event)
        if (!target) return
        event.preventDefault()
        event.stopPropagation()
        dispatch(target, event)
        if (event.pointerType === 'mouse' && !event.shiftKey && !event.ctrlKey && !event.metaKey) {
            dragBeat = target.beat
        }
    }, true)
    element.addEventListener('pointermove', event => {
        if (!dragBeat || !(event.buttons & 1)) {
            dragBeat = null
            return
        }
        const target = hit(event)
        if (!target || target.beat === dragBeat) return
        dragBeat = target.beat
        dispatch(target, event, true)
    })
    const endDrag = () => { dragBeat = null }
    element.addEventListener('pointerup', endDrag)
    element.addEventListener('pointercancel', endDrag)
}

// Track the actual score loaded into an alphaTab instance, not just its dataset.
const editorLoadedScores = new WeakMap()
const routineLoopModes = new WeakMap()
// Editor previews have independent, per-element transport controls.
// Routine/practice preference functions remain unchanged.
const editorPreviewOptions = new WeakMap()
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
        fixAlphaTabMidiInfoGetter(alphaTabModule)
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
        .replace(/\\tempo\s+\d+(?:\.\d+)?/i, '')

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
                includeNoteBounds: isAdminPreview,
                ...(isAdminPreview && !originalMusicXml ? { enableLazyLoading: false } : {}),
            },

            display: {
                staveProfile: 'Tab',
                ...(isAdminPreview && !originalMusicXml ? { stretchForce: 1.5 } : {}),
                // Keep playback-only MusicXML scores within the editor viewport.
                // Other Practice scores retain alphaTab's default page padding.
                ...(isAdminPreview && originalMusicXml ? { padding: [16, 18] } : {}),
            },

            player: {
                playerMode: alphaTab.PlayerMode.EnabledSynthesizer,
                soundFont: '/soundfont/sonivox.sf2',

                enableUserInteraction: true,
                enableCursor: true,
                enableAnimatedBeatCursor: true,
                enableElementHighlighting: !isAdminPreview,
                scrollElement: scoreScrollContainer(element),

                scrollMode: isAdminPreview
                    ? alphaTab.ScrollMode.Off
                    : alphaTab.ScrollMode.Continuous,
            },
        })

        if (isAdminPreview) {
            installEditorScoreSelection(element, api)
            api.postRenderFinished.on(() => updateEditorScoreCaret(element, api))
        }

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
            if (isAdminPreview) {
                element.classList.toggle('is-playing', args.state === alphaTab.synth.PlayerState.Playing)
            }
            waitingForScoreStart = args.state === alphaTab.synth.PlayerState.Playing
            dispatchPlaybackState(
                element,
                args.state,
                alphaTab.synth.PlayerState.Playing
            )
        })

        // Register before any MIDI is loaded: adding a midiLoaded listener later
        // triggers alphaTab 1.8.4's recursive loadedMidiInfo getter.
        installMidiReloadListener(api)

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
            api.tex(applyMetadata(editorPreviewAlphaTex(pattern, element.dataset.scoreEditable === 'true'), { bpm }))
        }

        instances.set(element, api)
        readyPromises.set(element, readyPromise)
        renderedBpms.set(element, originalMusicXml ? musicXmlFirstTempo(originalMusicXml, bpm) : bpm)

        await readyPromise
        if (isAdminPreview) editorLoadedScores.set(element, originalMusicXml ?? pattern)

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

    const renderedAlphaTex = applyMetadata(editorPreviewAlphaTex(alphaTex, element.dataset.scoreEditable === 'true'), metadata)

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

// Local-only SoundFont auditions, not saved into tablatures.
// Keep the preferred acoustic preset at program 0.
const auditionFonts = {
    acoustic: { url: '/soundfont/acoustic.sf2', program: 0 },
}
const auditionFontStates = new WeakMap()

function selectAuditionFont(api, selected) {
    let state = auditionFontStates.get(api)
    if (!state) {
        state = { current: '', pending: Promise.resolve() }
        auditionFontStates.set(api, state)
    }
    // Serialize changes to avoid a late callback from an older load
    // replacing the currently selected sound.
    state.pending = state.pending.catch(() => {}).then(async () => {
        if (state.current === selected) return
        const url = auditionFonts[selected]?.url ?? '/soundfont/sonivox.sf2'
        await new Promise((resolve, reject) => {
            api.soundFontLoaded.on(resolve)
            try {
                if (!api.loadSoundFont(url, false)) {
                    reject(new Error('Unable to load ' + url))
                }
            } catch (error) { reject(error) }
        })
        state.current = selected
    })
    return state.pending
}

window.addEventListener('alphatab:preview-draft', async event => {
    const { element, alphaTex, musicXml, bpm, play, instrument, transportOptions, renderOnly, auditionSound } = event.detail ?? {}
    if (!element) return

    if (!play && !renderOnly) {
        playbackRequests.set(element, ++playbackGeneration)
        const api = instances.get(element)
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
    const selectedAudition = !musicXml && play && Object.hasOwn(auditionFonts, auditionSound) ? auditionSound : ''
    const program = selectedAudition ? auditionFonts[selectedAudition].program : Number(instrument ?? 25)
    let pattern = alphaTex
        ? applyMetadata(editorPreviewAlphaTex(alphaTex, element.dataset.scoreEditable === 'true'), { bpm: Number(bpm) || 100, instrument: program })
        : null
    // MIDI program 0 is a piano. Without an explicit tuning alphaTab gives
    // its TAB staff zero strings, then rejects every fret.string note.
    // This is a playback-only adjustment for the local SF2 audition.
    if (selectedAudition && pattern) {
        pattern = pattern.replace(/(\\staff\s*\{\s*tabs\s*\})/i,
            '$1' + "\n\\tuning (E4 B3 G3 D3 A2 E2)")
    }
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
        window.dispatchEvent(new CustomEvent('alphatab:load-error', {
            detail: { element, message: error?.message ?? 'Could not load the score' },
        }))
        return
    }
    if (!api || !element.isConnected || playbackRequests.get(element) !== request || playbackGeneration !== request) return

    if (activeApi && activeApi !== api) activeApi.stop()
    activeApi = api
    const scoreKey = musicXml ?? pattern
    if (alreadyMounted && editorLoadedScores.get(element) !== scoreKey) {
        api.stop()
        if (musicXml) {
            // Native file loading is asynchronous. Never play the previous
            // score while alphaTab is still importing the new MusicXML.
            try {
                await loadReplacementMusicXml(api, musicXml)
            } catch (error) {
                console.error('alphaTab could not load the imported score:', error)
                window.dispatchEvent(new CustomEvent('alphatab:load-error', {
                    detail: { element, message: error?.message ?? 'Could not load the MusicXML score' },
                }))
                return
            }
        } else api.tex(pattern)
        editorLoadedScores.set(element, scoreKey)
    }
    if (playbackRequests.get(element) !== request || playbackGeneration !== request) return
    // Change only the editor synth bank, never the stored AlphaTex.
    if (element.dataset.adminPreview === 'true' && !renderOnly) {
        try {
            if (selectedAudition || auditionFontStates.get(api)?.current) {
                await selectAuditionFont(api, selectedAudition)
            }
        } catch (error) {
            if (playbackRequests.get(element) === request) {
                window.dispatchEvent(new CustomEvent('alphatab:load-error', {
                    detail: { element, message: error?.message ?? 'Unable to load SoundFont' },
                }))
            }
            return
        }
    }
    if (playbackRequests.get(element) !== request || playbackGeneration !== request) return
    if (renderOnly) {
        api.stop()
        element.classList.remove('is-playing')
        return
    }
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
    if (activeApi) setExerciseLoopMode(activeApi, false)
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
        api.tex(applyMetadata(editorPreviewAlphaTex(decodeBase64(encodedAlphaTex), element.dataset.scoreEditable === 'true'), { bpm }))
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
    // Seeking updates the playback position directly. Count-in is not
    // restarted when seeking during its lead-in (known alphaTab limitation).
    api.timePosition = position
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

window.addEventListener('alphatab:editor-caret', event => {
    const { element, selection } = event.detail ?? {}
    if (!element) return
    if (selection) editorScoreCursors.set(element, selection)
    else editorScoreCursors.delete(element)
    updateEditorScoreCaret(element, instances.get(element))
})
