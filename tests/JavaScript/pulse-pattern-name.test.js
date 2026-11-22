import assert from 'node:assert/strict'
import test from 'node:test'

import { interaction } from '../../resources/js/metronome/pulse-editor/interaction.js'

function createEditor() {
    const editor = {
        ...interaction(),
        patternName: '',
        pulsePatternNameAction: null,
        pulsePatternNameInput: '',
        isPulsePatternNameDialogOpen: false,
        isPulsePatternNameSaving: false,
        $refs: {
            pulsePatternNameDialog: {
                showModal() {},
                close() {},
            },
            pulsePatternNameInput: {
                focus() {},
                select() {},
            },
        },
        $nextTick(callback) { callback() },
        showToast() {},
        async savePulsePattern() { return true },
        async savePulsePatternAs() { return true },
    }

    return editor
}

test('new pulse pattern asks for a name before saving', async () => {
    const editor = createEditor()
    let saves = 0
    editor.savePulsePattern = async () => {
        saves++
        return true
    }

    assert.equal(editor.openPulsePatternNameDialog('new'), true)
    assert.equal(saves, 0)
    assert.equal(editor.isPulsePatternNameDialogOpen, true)
    editor.pulsePatternNameInput = '  Nu metal beat  '
    assert.equal(await editor.submitPulsePatternName(), true)
    assert.equal(saves, 1)
    assert.equal(editor.patternName, 'Nu metal beat')
    assert.equal(editor.isPulsePatternNameDialogOpen, false)
})

test('save as copy uses a new name, not the existing record', async () => {
    const editor = createEditor()
    editor.patternName = 'Original'
    let copied = false
    editor.savePulsePatternAs = async () => {
        copied = true
        return true
    }

    editor.openPulsePatternNameDialog('copy')
    assert.equal(editor.pulsePatternNameInput, 'Original')
    editor.pulsePatternNameInput = 'Variation'
    assert.equal(await editor.submitPulsePatternName(), true)
    assert.equal(copied, true)
    assert.equal(editor.patternName, 'Variation')
})

test('canceling the name dialog does not save anything', () => {
    const editor = createEditor()
    editor.openPulsePatternNameDialog('new')
    editor.pulsePatternNameInput = 'Abandoned'
    assert.equal(editor.closePulsePatternNameDialog(), true)
    assert.equal(editor.patternName, '')
    assert.equal(editor.pulsePatternNameInput, '')
})

test('failed save keeps the dialog open and restores previous name', async () => {
    const editor = createEditor()
    editor.patternName = 'Original'
    editor.savePulsePatternAs = async () => false

    editor.openPulsePatternNameDialog('copy')
    editor.pulsePatternNameInput = 'Unsuccessful copy'
    assert.equal(await editor.submitPulsePatternName(), false)
    assert.equal(editor.patternName, 'Original')
    assert.equal(editor.pulsePatternNameInput, 'Unsuccessful copy')
    assert.equal(editor.isPulsePatternNameDialogOpen, true)
    assert.equal(editor.isPulsePatternNameSaving, false)
})

test('blank names are not saved', async () => {
    const editor = createEditor()
    editor.openPulsePatternNameDialog('new')
    editor.pulsePatternNameInput = '   '
    assert.equal(await editor.submitPulsePatternName(), false)
    assert.equal(editor.isPulsePatternNameDialogOpen, true)
})
