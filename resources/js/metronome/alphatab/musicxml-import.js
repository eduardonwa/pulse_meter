import { createTabDraft, TAB_DURATIONS } from './tab-editor.js'

const SUPPORTED_DENOMINATORS = [2, 4, 8, 16]

function child(element, tag) {
    return Array.from(element?.children ?? []).find(node => node.localName === tag) ?? null
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
    if (child(note, 'time-modification') || children(note, 'dot').length) {
        throw new Error('Dotted notes and tuplets are not supported yet')
    }
    const durationTicks = requiredPositiveInteger(Number(value(note, 'duration')), 'note duration')
    const duration = (4 * divisions) / durationTicks
    if (!TAB_DURATIONS.includes(duration)) {
        throw new Error('This MusicXML uses note lengths the tablature editor cannot represent yet')
    }
    if (child(note, 'rest')) return { rest: true, duration, chord: false }
    const technical = child(child(note, 'notations'), 'technical')
    const string = Number(value(technical, 'string'))
    const fret = Number(value(technical, 'fret'))
    if (!Number.isInteger(string) || string < 1 || string > 6 ||
        !Number.isInteger(fret) || fret < 0 || fret > 24) {
        throw new Error('MusicXML must contain valid guitar string and fret positions (1–6, 0–24)')
    }
    return { string, fret, duration, chord: Boolean(child(note, 'chord')) }
}

function readMeasure(measure, divisions, signature) {
    const beats = []
    for (const node of Array.from(measure.children)) {
        if (['backup', 'forward'].includes(node.localName)) {
            throw new Error('Multiple voices and offsets are not supported yet')
        }
        if (node.localName !== 'note') continue
        if (value(node, 'voice') && value(node, 'voice') !== '1') {
            throw new Error('Multiple voices are not supported yet')
        }
        const note = parseNote(node, divisions)
        if (note.chord) {
            const previous = beats.at(-1)
            if (!previous || previous.rest || previous.duration !== note.duration) {
                throw new Error('MusicXML contains an unsupported chord')
            }
            const notes = previous.notes ?? [{ string: previous.string, fret: previous.fret }]
            if (notes.some(item => item.string === note.string)) {
                throw new Error('Multiple notes on the same string cannot form a chord')
            }
            notes.push({ string: note.string, fret: note.fret })
            beats[beats.length - 1] = { duration: note.duration, notes }
        } else {
            const { chord, ...single } = note
            beats.push(single)
        }
    }
    if (!beats.length) throw new Error('Empty measures are not supported yet')
    const total = beats.reduce((sum, note) => sum + 16 / note.duration, 0)
    const capacity = signature.numerator * 16 / signature.denominator
    if (total !== capacity) {
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
    if (/<!DOCTYPE|<!ENTITY/i.test(xmlText)) {
        throw new Error('MusicXML files with document type declarations are not supported')
    }
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
    let numerator = 4
    let denominator = 4
    let divisions = 1
    let bpm = 100
    const sections = []
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
        const tempo = Number(Array.from(measure.getElementsByTagName('sound'))
            .find(node => node.hasAttribute('tempo'))?.getAttribute('tempo'))
        if (Number.isFinite(tempo) && tempo >= 30 && tempo <= 400) bpm = Math.round(tempo)
        const signature = { numerator, denominator }
        const measureBeats = readMeasure(measure, divisions, signature)
        if (!active || active.numerator !== numerator || active.denominator !== denominator) {
            active = { numerator, denominator, bpm, firstMeasure: i + 1, lastMeasure: i + 1,
                draft: createTabDraft() }
            sections.push(active)
        }
        active.lastMeasure = i + 1
        if (active.draft.beats.length) active.draft.breaks.push(active.draft.beats.length)
        active.draft.beats.push(...measureBeats)
    }
    if (!sections.length) throw new Error('No measures were found')
    const title = value(child(score, 'work'), 'work-title') ??
        value(score, 'movement-title') ?? 'Imported tablature'
    return { title, sections }
}
