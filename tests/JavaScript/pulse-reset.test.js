import assert from 'node:assert/strict'
import test from 'node:test'

import { sources } from '../../resources/js/metronome/pulse-editor/sources.js'
import { collections } from '../../resources/js/metronome/pulse-editor/collections.js'
import { pattern } from '../../resources/js/metronome/pulse-editor/pattern.js'
import { draft } from '../../resources/js/metronome/pulse-editor/draft.js'

function createEditor() {
    const editor = {
        ...sources(),
        ...collections(),
        ...pattern(),
        ...draft(),
        pulsePresets: [
            { id: 1, name: 'Common Time', numerator: 4, denominator: 4, collection: 'core' },
            { id: 2, name: 'Triple Time', numerator: 3, denominator: 4, collection: 'core' },
        ],
        timeSignature: { numerator: 7, denominator: 8 },
        grouping: [7],
        subdivision: 1,
        pattern: [
            { sound: 'accent', groupStart: true },
            ...Array.from({ length: 6 }, () => ({ sound: 'click', groupStart: false })),
        ],
        pulseDraft: { origin: 'user', sourceId: 42, isDirty: true },
        pulseBaseline: null,
        toolTether: { active: false },
        editorTool: null,
        intervalId: null,
        currentBeat: 1,
        currentSubdivision: 0,
        patternName: 'Saved groove',
        rememberPulseSource(value) { this.remembered = value },
        cancelToolTether() { this.editorTool = null },
    }
    return editor
}

test('Reset Pulse cancels safely when draft has unsaved changes', async () => {
    const editor = createEditor()
    const originalWindow = globalThis.window
    globalThis.window = { confirm: () => false }
    try {
        assert.equal(await editor.resetPulse(), false)
        assert.equal(editor.timeSignature.numerator, 7)
        assert.equal(editor.pulseDraft.sourceId, 42)
    } finally {
        globalThis.window = originalWindow
    }
})

test('Reset Pulse starts a new 4/4 draft and preserves saved patterns', async () => {
    const editor = createEditor()
    editor.userPatterns = [{ id: 42, name: 'Saved groove' }]
    const originalWindow = globalThis.window
    globalThis.window = { confirm: () => true }
    try {
        assert.equal(await editor.resetPulse(), true)
        assert.deepEqual(editor.timeSignature, { numerator: 4, denominator: 4 })
        assert.deepEqual(editor.grouping, [4])
        assert.deepEqual(editor.pattern.map(item => item.sound), [
            'accent', 'click', 'click', 'click',
        ])
        assert.equal(editor.pulseDraft.origin, 'new')
        assert.equal(editor.pulseDraft.isDirty, false)
        assert.equal(editor.patternName, '')
        assert.equal(editor.remembered, 'new')
        assert.equal(editor.userPatterns.length, 1)
    } finally {
        globalThis.window = originalWindow
    }
})

test('Common Time is hidden from presets while other core presets remain', () => {
    const editor = createEditor()
    assert.deepEqual(editor.getPulsePresetsByCollection('core').map(p => p.name), [
        'Triple Time',
    ])
    assert.deepEqual(editor.getPulsePresetCollections(), ['core'])
})
