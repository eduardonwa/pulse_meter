import { createTabDraft, TAB_DURATIONS, tabBeatUnits, tabTieSource, tabLegatoDirection, tabTempoAt, tabSignatureAt } from './tab-editor.js'

const SUPPORTED_DENOMINATORS = [2, 4, 8, 16]

function child(element, tag) {
    return children(element, tag)[0] ?? null
}

function children(element, tag) {
    return Array.from(element?.children ?? []).filter(node => node.localName === tag)
}

function value(element, tag) {
    return child(element, tag)?.textContent?.trim() ?? null
}

function requiredPositiveInteger(number, label) {
    if (!Number.isInteger(number) || number < 1) {
        throw new Error('MusicXML contains an invalid ' + label)
    }
    return number
}

function parseNote(note, divisions) {
    const dots = children(note, 'dot')
    if (dots.length > 1) throw new Error('Double-dotted notes are not supported yet')
    const modification = child(note, 'time-modification')
    const actual = modification ? Number(value(modification, 'actual-notes')) : null
    const normal = modification ? Number(value(modification, 'normal-notes')) : null
    if (modification && (actual !== 3 || normal !== 2)) {
        throw new Error('Only 3:2 triplets are supported')
    }
    const durationTicks = requiredPositiveInteger(Number(value(note, 'duration')), 'note duration')
    const duration = 4 * divisions / durationTicks * (dots.length ? 1.5 : 1) * (modification ? 2 / 3 : 1)
    if (!TAB_DURATIONS.includes(duration)) {
        throw new Error('This MusicXML uses note lengths the tablature editor cannot represent yet')
    }
    const rhythm = { ...(dots.length ? { dotted: true } : {}), ...(modification ? { tuplet: 3 } : {}) }
    if (child(note, 'rest')) return { rest: true, duration, ...rhythm, chord: false }
    const technical = child(child(note, 'notations'), 'technical')
    const string = Number(value(technical, 'string') ?? NaN)
    const fret = Number(value(technical, 'fret') ?? NaN)
    if (!Number.isInteger(string) || string < 1 || string > 6 ||
        !Number.isInteger(fret) || fret < 0 || fret > 24) {
        throw new Error('MusicXML must contain valid guitar string and fret positions (1–6, 0–24)')
    }
    const palmMute = value(child(note, 'play'), 'mute') === 'palm'
    const connections = noteConnections(note)
    return { string, fret, duration, ...rhythm, ...(palmMute ? { palmMute: true } : {}), chord: Boolean(child(note, 'chord')), ...(connections.length ? { connections } : {}) }
}

// Retain connection endpoints until the complete section is available:
// ties and slides can cross measure boundaries.
function noteConnections(note) {
    const notation = child(note, 'notations')
    const technical = child(notation, 'technical')
    const ties = [...children(note, 'tie'), ...children(notation, 'tied')]
    const result = [...new Set(ties.map(node => node.getAttribute('type')))]
        .map(type => ({ kind: 'tie', type, number: '1' }))
    for (const [parent, tags] of [[notation, ['slide', 'glissando', 'slur']], [technical, ['hammer-on', 'pull-off']]]) {
        for (const kind of tags) {
            for (const node of children(parent, kind)) {
                result.push({ kind, type: node.getAttribute('type'), number: node.getAttribute('number') ?? '1' })
            }
        }
    }
    return result
}

function resolveConnections(beats, limitations) {
    const endpoints = beats.map(beat => beat.connections ?? [])
    const clean = beats.map(({ connections, ...beat }) => beat)
    const consumed = endpoints.map(() => new Set())
    const warn = kind => limitations.add('This score contains ' + kind + ' connections that cannot be represented by the editable tablature.')
    for (let i = 0; i < endpoints.length; i++) {
        for (const [index, start] of endpoints[i].entries()) {
            if (start.type !== 'start' || consumed[i].has(index)) continue
            const next = endpoints[i + 1] ?? []
            const stop = next.findIndex((end, j) => !consumed[i + 1].has(j) &&
                end.type === 'stop' && end.kind === start.kind && end.number === start.number)
            if (stop < 0 || clean[i].notes || clean[i + 1]?.notes) continue
            const direction = tabLegatoDirection(clean, i)
            if (start.kind === 'tie' && tabTieSource(clean, i + 1) !== null) {
                clean[i + 1].tie = true
            } else if ((start.kind === 'slide' && direction) ||
                (start.kind === 'hammer-on' && direction === 'H') ||
                (start.kind === 'pull-off' && direction === 'P')) {
                // A matching slur accompanying a slide denotes a legato slide.
                // An unaccompanied slur is ambiguous and remains unsupported.
                const slurStart = endpoints[i].findIndex((e, j) => !consumed[i].has(j) &&
                    e.kind === 'slur' && e.type === 'start' &&
                    next.some((n, k) => !consumed[i + 1].has(k) &&
                        n.kind === 'slur' && n.type === 'stop' && n.number === e.number))
                if (slurStart >= 0) {
                    const slurStop = next.findIndex((e, j) => !consumed[i + 1].has(j) &&
                        e.kind === 'slur' && e.type === 'stop' && e.number === endpoints[i][slurStart].number)
                    consumed[i].add(slurStart)
                    consumed[i + 1].add(slurStop)
                }
                if (start.kind === 'slide') clean[i].slide = slurStart >= 0 ? 'sl' : 'ss'
                else clean[i].legato = true
            } else continue
            consumed[i].add(index)
            consumed[i + 1].add(stop)
        }
    }
    for (let i = 0; i < endpoints.length; i++) {
        for (const [index, endpoint] of endpoints[i].entries()) {
            if (!consumed[i].has(index)) warn(endpoint.kind)
        }
        if (clean[i].slide && clean[i].legato) warn('conflicting slide and legato')
    }
    return clean
}

function fullMeasureRests(capacity) {
    const rests = []
    let remaining = capacity
    for (const duration of TAB_DURATIONS) {
        const units = 16 / duration
        while (remaining >= units) {
            rests.push({ rest: true, duration })
            remaining -= units
        }
    }
    return rests
}

// A Guitar Pro export may contain the same instrument twice: standard
// notation and TAB. Prefer the explicitly declared TAB staff, not a voice ID.
function tablatureStaff(part) {
    for (const measure of children(part, 'measure')) {
        const clefs = children(child(measure, 'attributes'), 'clef')
        const tab = clefs.find(clef => value(clef, 'sign') === 'TAB')
        if (tab) return tab.getAttribute('number') || '1'
    }
    return null
}

function staffOf(note) {
    return value(note, 'staff') ?? '1'
}

// Track the MusicXML cursor across both staves so that filtering does not
// accidentally flatten simultaneous voices, gaps or overlapping notes.
function measureNotes(measure, staff) {
    const selected = []
    let cursor = 0
    let onset = 0
    let selectedEnd = 0
    let voice = null
    for (const node of Array.from(measure.children)) {
        if (['backup', 'forward'].includes(node.localName)) {
            if (staff === null) throw new Error('Multiple voices and offsets are not supported yet')
            const ticks = requiredPositiveInteger(Number(value(node, 'duration')), 'offset duration')
            cursor += node.localName === 'backup' ? -ticks : ticks
            if (cursor < 0) throw new Error('Invalid MusicXML offset')
            continue
        }
        if (node.localName !== 'note') continue
        const chord = Boolean(child(node, 'chord'))
        const duration = requiredPositiveInteger(Number(value(node, 'duration')), 'note duration')
        if (!chord) {
            onset = cursor
            cursor += duration
        }
        if (staff !== null && staffOf(node) !== staff) continue
        const noteVoice = value(node, 'voice') ?? '1'
        if ((staff === null && noteVoice !== '1') || (voice !== null && voice !== noteVoice)) {
            throw new Error('Multiple voices are not supported yet')
        }
        voice = noteVoice
        if (chord ? !selected.length || onset !== selectedEnd - duration : onset !== selectedEnd) {
            throw new Error('Overlapping notes or offsets are not supported yet')
        }
        if (!chord) selectedEnd = onset + duration
        selected.push(node)
    }
    if (!selected.length && children(measure, 'note').length) {
        throw new Error('No notes found on the tablature staff')
    }
    return selected
}

function readMeasure(measure, divisions, signature, staff) {
    const beats = []
    const capacity = signature.numerator * 16 / signature.denominator
    const notes = measureNotes(measure, staff)
    if (notes.length === 1 && child(notes[0], 'rest')?.getAttribute('measure') === 'yes') {
        return fullMeasureRests(capacity)
    }
    for (const node of notes) {
        const note = parseNote(node, divisions)
        if (note.chord) {
            const previous = beats.at(-1)
            if (!previous || previous.rest || previous.duration !== note.duration || Boolean(previous.dotted) !== Boolean(note.dotted) || previous.tuplet !== note.tuplet) {
                throw new Error('MusicXML contains an unsupported chord')
            }
            const notes = previous.notes ?? [{ string: previous.string, fret: previous.fret }]
            if (notes.some(item => item.string === note.string)) {
                throw new Error('Multiple notes on the same string cannot form a chord')
            }
            notes.push({ string: note.string, fret: note.fret })
            if (Boolean(previous.palmMute) !== Boolean(note.palmMute)) {
                throw new Error('Mixed palm mute within a chord is not supported yet')
            }
            const { string, fret, connections: previousConnections = [], ...rhythm } = previous
            const connections = [...previousConnections, ...(note.connections ?? [])]
            beats[beats.length - 1] = { ...rhythm, notes, ...(connections.length ? { connections } : {}) }
        } else {
            const { chord, ...single } = note
            beats.push(single)
        }
    }
    if (!beats.length) beats.push(...fullMeasureRests(capacity))
    const total = beats.reduce((sum, note) => sum + tabBeatUnits(note), 0)
    if (Math.abs(total - capacity) > 1e-7) {
        throw new Error('Measure ' + measure.getAttribute('number') +
            ' has ' + total + ' sixteenth-note units but ' +
            signature.numerator + '/' + signature.denominator + ' requires ' + capacity)
    }
    return beats
}

// Read the MusicXML subset supported by the simple Dorelog tab editor.
// Reject unsupported notation instead of importing a rhythmically altered score.
export function parseMusicXmlTab(xmlText, parser = null) {
    if (typeof xmlText !== 'string' || xmlText.length > 2_000_000) {
        throw new Error('Choose a MusicXML file smaller than 2 MB')
    }
    // Guitar Pro exports an external MusicXML DTD. Drop that declaration before
    // parsing, and reject entity definitions rather than resolving external data.
    if (/<!ENTITY|<!DOCTYPE[^>]*\[/i.test(xmlText)) {
        throw new Error('MusicXML with custom entities is not supported')
    }
    xmlText = xmlText.replace(/<!DOCTYPE[^>]*>/i, '')
    const xmlParser = parser ?? (typeof DOMParser !== 'undefined' ? new DOMParser() : null)
    if (!xmlParser) throw new Error('XML parsing is unavailable in this browser')
    const document = xmlParser.parseFromString(xmlText, 'application/xml')
    if (document.getElementsByTagName('parsererror').length) throw new Error('Invalid MusicXML file')
    const score = document.documentElement
    if (score?.localName !== 'score-partwise') {
        throw new Error('Only partwise MusicXML scores are supported')
    }
    const parts = children(score, 'part')
    const part = parts.find(item => Array.from(item.getElementsByTagName('fret')).length > 0)
    if (!part) throw new Error('No tablature with string and fret information was found')
    const staff = tablatureStaff(part)
    let numerator = 4
    let denominator = 4
    let divisions = 1
    let bpm = 100
    const sections = []
    const warnings = []
    const limitations = new Set()
    let active = null
    const measures = children(part, 'measure')
    for (const [i, measure] of measures.entries()) {
        const attributes = child(measure, 'attributes')
        const divisionsText = value(attributes, 'divisions')
        if (divisionsText !== null) divisions = requiredPositiveInteger(Number(divisionsText), 'divisions')
        const time = child(attributes, 'time')
        if (time) {
            numerator = requiredPositiveInteger(Number(value(time, 'beats')), 'numerator')
            denominator = requiredPositiveInteger(Number(value(time, 'beat-type')), 'denominator')
        }
        if (numerator > 16 || !SUPPORTED_DENOMINATORS.includes(denominator)) {
            throw new Error('Time signatures must use 1–16 over 2, 4, 8 or 16')
        }
        // Only bar-start tempo instructions are representable in the editor.
        // Keep the native score available when a change occurs within a bar.
        let cursor = 0
        let measureBpm = bpm
        for (const node of measure.children) {
            const duration = Number(value(node, 'duration'))
            if (node.localName === 'note' && !child(node, 'chord')) cursor += duration
            else if (node.localName === 'backup') cursor -= duration
            else if (node.localName === 'forward') cursor += duration
            const sounds = node.localName === 'sound' ? [node] : Array.from(node.getElementsByTagName('sound'))
            for (const sound of sounds.filter(item => item.hasAttribute('tempo'))) {
                const tempo = Number(sound.getAttribute('tempo'))
                if (!Number.isInteger(tempo) || tempo < 30 || tempo > 400) {
                    limitations.add('This score contains tempo values outside the editable BPM range (30–400, whole numbers).')
                    continue
                }
                const offset = Number(value(sound, 'offset') ?? value(node, 'offset') ?? 0)
                if (cursor + offset !== 0) {
                    if (tempo !== bpm) limitations.add('This score contains tempo changes within measures. The editor supports changes at the beginning of a measure.')
                } else if (measureBpm !== bpm && measureBpm !== tempo) {
                    limitations.add('This score contains conflicting tempo instructions at a measure boundary.')
                } else measureBpm = tempo
                bpm = tempo
            }
        }
        const signature = { numerator, denominator }
        let measureBeats
        try {
            measureBeats = readMeasure(measure, divisions, signature, staff)
        } catch (error) {
            warnings.push('Measure ' + (i + 1) + ': ' + error.message)
            active = null
            continue
        }
        if (!active) {
            active = { numerator, denominator, bpm: measureBpm, firstMeasure: i + 1, lastMeasure: i + 1,
                draft: createTabDraft() }
            sections.push(active)
        }
        if (active.draft.beats.length) {
            const measureIndex = i + 1 - active.firstMeasure
            const previousSignature = tabSignatureAt(active.draft, measureIndex - 1, active.numerator, active.denominator)
            if (numerator !== previousSignature.numerator || denominator !== previousSignature.denominator) {
                active.draft.signatures[measureIndex] = { numerator, denominator }
            }
            const previousBpm = tabTempoAt(active.draft, measureIndex - 1, active.bpm)
            if (measureBpm !== previousBpm) active.draft.tempos[measureIndex] = measureBpm
            active.draft.breaks.push(active.draft.beats.length)
        }
        active.lastMeasure = i + 1
        active.draft.beats.push(...measureBeats)
    }
    if (!sections.length) throw new Error('No compatible measures found for editing. The full score is still available for playback.')
    for (const section of sections) {
        section.draft.beats = resolveConnections(section.draft.beats, limitations)
    }
    const title = value(child(score, 'work'), 'work-title') ??
        value(score, 'movement-title') ?? 'Imported tablature'
    return { title, sections, warnings, limitations: [...limitations] }
}
