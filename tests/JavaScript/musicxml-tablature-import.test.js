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
    assert.match(imported.warnings[0], /Measure 4: Dotted notes and tuplets/)
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
