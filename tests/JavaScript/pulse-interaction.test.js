import assert from 'node:assert/strict'
import test from 'node:test'

import {
    interaction,
} from '../../resources/js/metronome/pulse-editor/interaction.js'

function createClickEvent({
    tetherTrigger = false,
} = {}) {
    return {
        target: {
            closest() {
                return tetherTrigger
            },
        },
    }
}

test(
    'outside click cancels the Randomizer tool tether',
    () => {
        const editor = interaction()

        editor.editorTool = null
        editor.randomizerEditorTool = 'accent'
        editor.cancelCount = 0

        editor.cancelToolTether = () => {
            editor.cancelCount += 1
        }

        editor.handleToolTetherClick(
            createClickEvent()
        )

        assert.equal(editor.cancelCount, 1)
    }
)

test(
    'tool trigger click keeps the tether active',
    () => {
        const editor = interaction()

        editor.editorTool = null
        editor.randomizerEditorTool = 'accent'
        editor.cancelCount = 0

        editor.cancelToolTether = () => {
            editor.cancelCount += 1
        }

        editor.handleToolTetherClick(
            createClickEvent({
                tetherTrigger: true,
            })
        )

        assert.equal(editor.cancelCount, 0)
    }
)
