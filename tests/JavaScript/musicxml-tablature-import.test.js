import test from 'node:test'
import assert from 'node:assert/strict'
import { parseMusicXmlTab } from '../../resources/js/metronome/alphatab/musicxml-import.js'
import { layoutTabMeasures, serializeTabDraft } from '../../resources/js/metronome/alphatab/tab-editor.js'

// Minimal DOM tree for testing the browser parser's interpretation without
// requiring a browser implementation in Node's test runner.
function el(localName, content = [], attributes = {}) {
    const children = Array.isArray(content) ? content : []
    const ownText = Array.isArray(content) ? '' : String(content)
    return {
        localName, children,
        textContent: ownText || children.map(node => node.textContent).join(''),
        getAttribute(name) { return attributes[name] ?? null },
        hasAttribute(name) { return Object.hasOwn(attributes, name) },
        getElementsByTagName(name) {
            return children.flatMap(item => [
                ...(item.localName === name ? [item] : []),
                ...item.getElementsByTagName(name),
            ])
        },
    }
}

function note(duration, fret, options = {}) {
    return el('note', [
        el('duration', String(duration)),
        ...(options.tuplet ? [el('time-modification')] : []),
        el('notations', [el('technical', [
            el('string', '1'), el('fret', String(fret)),
        ])]),
    ])
}

function measure(index, numerator, denominator, lengths, options = {}) {
    return el('measure', [
        el('attributes', [
            el('divisions', '4'),
            el('time', [el('beats', String(numerator)), el('beat-type', String(denominator))]),
        ]),
        ...lengths.map((length, i) => note(length, i, { tuplet: options.tuplet && i === 0 })),
    ], { number: String(index) })
}

function fakeDocument() {
    const score = el('score-partwise', [
        el('work', [el('work-title', 'Test guitar exercise')]),
        el('part', [
            measure(1, 7, 8, Array(7).fill(2)),
            measure(2, 7, 8, Array(7).fill(2)),
            measure(3, 4, 4, Array(4).fill(4)),
            measure(4, 4, 4, Array(4).fill(4), { tuplet: true }),
            measure(5, 7, 8, Array(7).fill(2)),
        ]),
    ])
    return {
        documentElement: score,
        getElementsByTagName(name) { return score.getElementsByTagName(name) },
    }
}

test('MusicXML import separates meter changes and skips unsupported notes visibly', () => {
    let parsedXml = ''
    const parser = {
        parseFromString(text) {
            parsedXml = text
            return fakeDocument()
        },
    }
    const imported = parseMusicXmlTab(
        '<!DOCTYPE score-partwise PUBLIC "test" "example.dtd"><score-partwise/>',
        parser,
    )
    assert.equal(parsedXml.includes('<!DOCTYPE'), false)
    assert.equal(imported.title, 'Test guitar exercise')
    assert.deepEqual(imported.sections.map(section =>
        [section.firstMeasure, section.lastMeasure, section.numerator, section.denominator]
    ), [[1, 2, 7, 8], [3, 3, 4, 4], [5, 5, 7, 8]])
    assert.equal(imported.warnings.length, 1)
    assert.match(imported.warnings[0], /Measure 4: Only 3:2 triplets/)
    const first = imported.sections[0]
    assert.equal(first.draft.beats.length, 14)
    assert.deepEqual(first.draft.breaks, [7])
    assert.ok(layoutTabMeasures(first.draft, 7, 8).every(item => item.complete))
    assert.match(serializeTabDraft(first.draft, 7, 120, 8), /\\ts 7 8/)
})

test('MusicXML rejects entity declarations', () => {
    assert.throws(
        () => parseMusicXmlTab('<!DOCTYPE score-partwise [<!ENTITY x "abc">]>'),
        /custom entities/,
    )
})

test('MusicXML preserves dotted notes and three-to-two triplets', () => {
    const score = el('score-partwise', [
        el('part', [
            el('measure', [
                el('attributes', [el('divisions', '12'), el('time', [el('beats', '4'), el('beat-type', '4')])]),
                el('note', [el('duration', '18'), el('dot'), el('notations', [el('technical', [el('string', '1'), el('fret', '5')])])]),
                el('note', [el('duration', '6'), el('notations', [el('technical', [el('string', '1'), el('fret', '7')])])]),
                ...[9, 10, 11].map(fret => el('note', [
                    el('duration', '4'),
                    el('time-modification', [el('actual-notes', '3'), el('normal-notes', '2')]),
                    el('notations', [el('technical', [el('string', '1'), el('fret', String(fret))])]),
                ])),
                el('note', [el('duration', '12'), el('notations', [el('technical', [el('string', '1'), el('fret', '12')])])]),
            ], { number: '1' }),
        ]),
    ])
    const parser = { parseFromString() { return {
        documentElement: score,
        getElementsByTagName(name) { return score.getElementsByTagName(name) },
    } } }
    const imported = parseMusicXmlTab('<score-partwise/>', parser)
    assert.equal(imported.warnings.length, 0)
    const beats = imported.sections[0].draft.beats
    assert.equal(beats[0].dotted, true)
    assert.equal(beats[0].duration, 4)
    assert.deepEqual(beats.slice(2, 5).map(beat => beat.tuplet), [3, 3, 3])
    assert.ok(layoutTabMeasures(imported.sections[0].draft, 4, 4)[0].complete)
    const tex = serializeTabDraft(imported.sections[0].draft, 4, 120, 4)
    assert.ok(tex.includes('{ d }'))
    assert.ok(tex.includes('{ tu 3 }'))
})

test('MusicXML converts a genuinely empty measure into editable rests', () => {
    const score = el('score-partwise', [
        el('part', [
            measure(1, 7, 8, Array(7).fill(2)),
            el('measure', [el('attributes', [el('time', [el('beats', '7'), el('beat-type', '8')])])], { number: '2' }),
        ]),
    ])
    const parser = { parseFromString() { return {
        documentElement: score,
        getElementsByTagName(name) { return score.getElementsByTagName(name) },
    } } }
    const imported = parseMusicXmlTab('<score-partwise/>', parser)
    assert.equal(imported.warnings.length, 0)
    assert.equal(imported.sections.length, 1)
    assert.equal(imported.sections[0].lastMeasure, 2)
    const rests = imported.sections[0].draft.beats.slice(7)
    assert.ok(rests.length > 0 && rests.every(beat => beat.rest))
    assert.ok(layoutTabMeasures(imported.sections[0].draft, 7, 8).every(bar => bar.complete))
})

test('MusicXML full-measure rest with nonstandard duration becomes editable rests', () => {
    const score = el('score-partwise', [
        el('part', [
            measure(1, 7, 8, Array(7).fill(2)),
            el('measure', [
                el('attributes', [el('time', [el('beats', '7'), el('beat-type', '8')])]),
                el('note', [el('rest', [], { measure: 'yes' }), el('duration', '14')]),
            ], { number: '2' }),
        ]),
    ])
    const parser = { parseFromString() { return {
        documentElement: score,
        getElementsByTagName(name) { return score.getElementsByTagName(name) },
    } } }
    const imported = parseMusicXmlTab('<score-partwise/>', parser)
    assert.deepEqual(imported.warnings, [])
    assert.equal(imported.sections[0].lastMeasure, 2)
    assert.ok(layoutTabMeasures(imported.sections[0].draft, 7, 8).every(bar => bar.complete))
})

test('MusicXML with no editable measures explains that complete score playback remains available', () => {
    const score = el('score-partwise', [
        el('part', [
            el('measure', [
                el('attributes', [el('divisions', '4')]),
                el('note', [
                    el('duration', '4'),
                    el('notations', [el('technical', [el('string', '1'), el('fret', '5')])]),
                    el('time-modification', [
                        el('actual-notes', '5'),
                        el('normal-notes', '4'),
                    ]),
                ]),
            ], { number: '1' }),
        ]),
    ])
    const parser = { parseFromString() { return {
        documentElement: score,
        getElementsByTagName(name) { return score.getElementsByTagName(name) },
    } } }
    assert.throws(
        () => parseMusicXmlTab('<score-partwise/>', parser),
        /No compatible measures found for editing\. The full score is still available for playback\./,
    )
})


function guitarProMeasure(number, { tempo, gap = false, missingFret = false, connection = false } = {}) {
    const standard = [0, 1, 2, 3].map(() => el('note', [
        el('duration', '4'), el('staff', '1'), el('voice', '1'),
        el('notations', [el('technical')]),
    ]))
    const tab = [2, 4, 6, 8].map((fret, index) => el('note', [
        el('duration', '4'), el('staff', '2'), el('voice', '5'),
        el('notations', [
            el('technical', [el('string', '6'),
                ...(missingFret && index === 0 ? [] : [el('fret', String(fret))])]),
            ...(connection && index === 0 ? [el('slide', [], { type: 'start' })] : []),
        ]),
        ...(index === 1 ? [el('play', [el('mute', 'palm')])] : []),
    ]))
    return el('measure', [
        ...(number === 1 ? [el('attributes', [
            el('divisions', '4'),
            el('clef', [el('sign', 'G')], { number: '1' }),
            el('clef', [el('sign', 'TAB')], { number: '2' }),
        ])] : []),
        ...(tempo ? [el('direction', [el('sound', [], { tempo: String(tempo) })])] : []),
        ...standard, el('backup', [el('duration', '16')]),
        ...(gap ? [el('forward', [el('duration', '4')])] : []),
        ...tab,
    ], { number: String(number) })
}

function importMeasures(measures) {
    const score = el('score-partwise', [el('part', measures)])
    return parseMusicXmlTab('<score-partwise/>', {
        parseFromString() {
            return { documentElement: score, getElementsByTagName: name => score.getElementsByTagName(name) }
        },
    })
}

test('Guitar Pro dual-staff export imports the TAB voice without duplicating notes', () => {
    const imported = importMeasures([guitarProMeasure(1), guitarProMeasure(2)])
    assert.deepEqual(imported.warnings, [])
    assert.deepEqual(imported.limitations, [])
    assert.equal(imported.sections.length, 1)
    const { beats, breaks } = imported.sections[0].draft
    assert.deepEqual(beats.map(beat => beat.fret), [2, 4, 6, 8, 2, 4, 6, 8])
    assert.ok(beats.every(beat => beat.string === 6))
    assert.equal(beats[1].palmMute, true)
    assert.deepEqual(breaks, [4])
})

test('Guitar Pro import rejects offsets and missing fret rather than inventing notes', () => {
    const imported = importMeasures([
        guitarProMeasure(1), guitarProMeasure(2, { gap: true }),
        guitarProMeasure(3, { missingFret: true }),
    ])
    assert.equal(imported.warnings.length, 2)
    assert.match(imported.warnings[0], /Measure 2: Overlapping notes or offsets/)
    assert.match(imported.warnings[1], /Measure 3: MusicXML must contain valid guitar string and fret/)
})

test('MusicXML identifies tempo changes and unsupported connections separately from invalid measures', () => {
    const imported = importMeasures([
        guitarProMeasure(1, { tempo: 173 }),
        guitarProMeasure(2, { tempo: 183, connection: true }),
    ])
    assert.deepEqual(imported.warnings, [])
    assert.equal(imported.sections[0].bpm, 173)
    assert.equal(imported.limitations.length, 2)
    assert.match(imported.limitations[0], /tempo changes/)
    assert.match(imported.limitations[1], /slide connections/)
})


function connectionNote(fret, endpoints = [], technical = []) {
    return el('note', [
        el('duration', '4'),
        el('notations', [
            el('technical', [el('string', '1'), el('fret', String(fret)), ...technical]),
            ...endpoints,
        ]),
    ])
}

const endpoint = (kind, type, number = '1') => el(kind, [], { type, number })
const connectionMeasure = (number, notes) => el('measure', [
    el('attributes', [el('divisions', '4')]), ...notes,
], { number: String(number) })

test('MusicXML imports a tie across a measure boundary and a legato slide', () => {
    const imported = importMeasures([
        connectionMeasure(1, [connectionNote(2), connectionNote(3), connectionNote(5),
            connectionNote(7, [endpoint('tied', 'start')])]),
        connectionMeasure(2, [
            connectionNote(7, [endpoint('tied', 'stop')]),
            connectionNote(8, [endpoint('slur', 'start'), endpoint('slide', 'start', '3')]),
            connectionNote(4, [endpoint('slur', 'stop'), endpoint('slide', 'stop', '3')]),
            connectionNote(2),
        ]),
    ])
    assert.deepEqual(imported.warnings, [])
    assert.deepEqual(imported.limitations, [])
    const draft = imported.sections[0].draft
    assert.equal(draft.beats[4].tie, true)
    assert.equal(draft.beats[5].slide, 'sl')
    assert.ok(draft.beats.every(beat => !Object.hasOwn(beat, 'connections')))
    assert.match(serializeTabDraft(draft), /7\.1\{t\}/)
    assert.match(serializeTabDraft(draft), /8\.1\{sl\}/)
})

test('MusicXML imports explicit hammer-on, pull-off and ordinary slide endpoints', () => {
    const imported = importMeasures([
        connectionMeasure(1, [
            connectionNote(2, [], [endpoint('hammer-on', 'start')]),
            connectionNote(4, [], [endpoint('hammer-on', 'stop'), endpoint('pull-off', 'start')]),
            connectionNote(2, [endpoint('slide', 'start')], [endpoint('pull-off', 'stop')]),
            connectionNote(7, [endpoint('slide', 'stop')]),
        ]),
    ])
    assert.deepEqual(imported.limitations, [])
    const beats = imported.sections[0].draft.beats
    assert.equal(beats[0].legato, true)
    assert.equal(beats[1].legato, true)
    assert.equal(beats[2].slide, 'ss')
})

test('MusicXML does not guess the technique represented by an isolated slur', () => {
    const imported = importMeasures([
        connectionMeasure(1, [
            connectionNote(2, [endpoint('slur', 'start')]),
            connectionNote(4, [endpoint('slur', 'stop')]),
            connectionNote(5), connectionNote(7),
        ]),
    ])
    assert.match(imported.limitations[0], /slur connections/)
    assert.equal(imported.sections[0].draft.beats[0].legato, undefined)
})
