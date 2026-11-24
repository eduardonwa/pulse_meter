import assert from 'node:assert/strict'
import test from 'node:test'

import { pattern } from '../../resources/js/metronome/pulse-editor/pattern.js'
import { grouping } from '../../resources/js/metronome/pulse-editor/grouping.js'
import { interaction } from '../../resources/js/metronome/pulse-editor/interaction.js'
import { audioEngine } from '../../resources/js/metronome/audio-engine.js'

function createEditor() {
    const editor = {
        ...pattern(),
        ...grouping(),
        ...interaction(),
        timeSignature: { numerator: 4, denominator: 4 },
        subdivision: 4,
        grouping: [4],
        pattern: [],
        editorTool: 'groupStart',
        syncPulseDirty() {},
        cancelToolTether() { this.editorTool = null },
    }
    editor.pattern = editor.buildPatternFromGrouping([4])
    editor.applySubdivisionToPattern(editor.pattern, 4)
    return editor
}

test('4/4 sixteenths can form 3 + 3 + 3 + 3 + 4', () => {
    const editor = createEditor()
    const positions = [[1, 2], [2, 1], [3, 0], [4, null]]

    for (const [beat, index] of positions) {
        if (index === null) {
            assert.equal(editor.setGroupStart(beat, true), true)
        } else {
            editor.editorTool = 'groupStart'
            editor.applyEditorToolToSubdivision(beat, index)
        }
    }

    assert.deepEqual(editor.grouping, [.75, .75, .75, .75, 1])
    assert.equal(editor.getGroupingLabel(), '3 + 3 + 3 + 3 + 4')
    assert.deepEqual(editor.getPatternGroups().map(group => group.length), [3, 3, 3, 3, 4])
    assert.equal(editor.pattern[0].subdivisions[2].sound, 'accent')
})

test('subdivision group starts toggle without removing the first downbeat', () => {
    const editor = createEditor()
    assert.equal(editor.setGroupStart(1, false), false)
    assert.equal(editor.setGroupStart(1, true, null, 2), true)
    assert.equal(editor.setGroupStart(1, false, null, 2), true)
    assert.deepEqual(editor.grouping, [4])
})

test('legacy beat-only groups retain their beat-unit labels', () => {
    const editor = createEditor()
    assert.equal(editor.setGroupStart(2, true), true)
    assert.equal(editor.getGroupingLabel(), '1 + 3')
    assert.deepEqual(editor.grouping, [1, 3])
})

test('pulse playback emits tones at subdivision group starts', () => {
    const editor = createEditor()
    editor.setGroupStart(1, true, null, 2)
    const player = {
        ...audioEngine(),
        timeSignature: editor.timeSignature,
        grouping: editor.grouping,
        pattern: editor.pattern,
        subdivision: 4,
        metronome: { mode: 'creative', bpm: 120 },
        creativeMode: 'pulse-editor',
        creativePlaybackMode: 'pulse',
        currentBeat: 1,
        currentSubdivision: 3,
        played: [],
        playPulseTone(tone) { this.played.push(tone) },
        tick() { throw new Error('click should not play') },
    }
    player.playPatternPosition()
    assert.equal(player.played.length, 1)
    assert.equal(player.played[0].isAccent, true)
    player.currentSubdivision = 1
    player.playPatternPosition()
    assert.equal(player.played.length, 1)
})
