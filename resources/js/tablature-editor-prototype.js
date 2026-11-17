import { Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm'
import { createTabDraft, TAB_STRINGS } from './metronome/alphatab/tab-editor.js'
import { tablatureEditorMethods } from './metronome/tablature-editor-controller.js'

window.Alpine = Alpine

// Standalone UI harness. The real editor still owns persistence, import and playback.
Alpine.data('tablatureRedesign', () => ({
    tabDraft: createTabDraft(),
    tabStringNames: TAB_STRINGS,
    tabViewportWidth: 900,
    tabSelectedIndex: 0,
    tabSelectedIndices: [],
    tabSelectionAnchor: null,
    tabInsertAtBar: false,
    tabUndoHistory: [],
    tabRedoHistory: [],
    tabDragging: false,
    tabDragAnchor: null,
    tabDragMoved: false,
    tabDigitPending: null,
    tabDigitTimer: null,
    stepForm: {
        time_signature_numerator: 4,
        time_signature_denominator: 4,
        bpm: 100,
    },
    showToast(message, type = 'error') {
        console[type === 'error' ? 'error' : 'log'](message)
    },
    ...tablatureEditorMethods(),
}))

Alpine.start()
