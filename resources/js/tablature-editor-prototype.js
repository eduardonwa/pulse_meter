import { Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm'
import { createTabDraft, TAB_STRINGS, serializeTabDraft, parseTabDraft } from './metronome/alphatab/tab-editor.js'
import { tablatureEditorMethods } from './metronome/tablature-editor-controller.js'

window.Alpine = Alpine

const PROTOTYPE_EXERCISE_KEY = 'dorelog:tab-editor-prototype:exercise-v1'

// Standalone UI harness. The real editor still owns persistence, import and playback.
Alpine.data('tablatureRedesign', () => ({
    tabDraft: createTabDraft(),
    prototypeEditorOpen: true,
    prototypeSaving: false,
    prototypeMessage: '',
    prototypeSaved: null,
    restorePrototypeSaved() {
        if (this.prototypeSaved) {
            const parsed = parseTabDraft(this.prototypeSaved.alpha_tex)
            this.tabDraft = parsed.draft
            this.stepForm = {
                ...this.stepForm,
                name: this.prototypeSaved.name,
                bpm: parsed.bpm,
                time_signature_numerator: parsed.numerator,
                time_signature_denominator: parsed.denominator,
            }
        } else {
            this.tabDraft = createTabDraft()
            this.stepForm = { name: 'Untitled exercise', bpm: 100, time_signature_numerator: 4, time_signature_denominator: 4 }
        }
        this.tabSelectedIndex = 0
        this.tabSelectedIndices = []
        this.tabSelectionAnchor = null
        this.tabInsertAtBar = false
        this.tabUndoHistory = []
        this.tabRedoHistory = []
    },
    loadPrototypeExercise() {
        try {
            const stored = window.localStorage.getItem(PROTOTYPE_EXERCISE_KEY)
            if (!stored) return
            const value = JSON.parse(stored)
            if (typeof value.name !== 'string' || typeof value.alpha_tex !== 'string') throw new Error('Invalid saved exercise')
            this.prototypeSaved = value
            this.restorePrototypeSaved()
            this.prototypeMessage = 'Loaded saved prototype exercise from this browser.'
        } catch (error) {
            console.error('Could not load prototype exercise', error)
            this.prototypeMessage = 'Unable to load a previously saved prototype exercise.'
        }
    },
    savePrototypeExercise(closeAfterSave = false) {
        if (this.prototypeSaving) return
        this.prototypeSaving = true
        try {
            const name = this.stepForm.name?.trim()
            if (!name) throw new Error('Enter an exercise name before saving.')
            const bpm = Number(this.stepForm.bpm)
            if (!Number.isInteger(bpm) || bpm < 30 || bpm > 400) throw new Error('BPM must be between 30 and 400.')
            const alpha_tex = serializeTabDraft(this.tabDraft, this.stepForm.time_signature_numerator, bpm, this.stepForm.time_signature_denominator)
            // Check the saved score can be loaded with the actual editor parser.
            parseTabDraft(alpha_tex)
            const saved = { name, alpha_tex }
            window.localStorage.setItem(PROTOTYPE_EXERCISE_KEY, JSON.stringify(saved))
            this.prototypeSaved = saved
            this.stepForm.name = name
            this.prototypeMessage = 'Saved in this browser (prototype only; not added to a routine).'
            if (closeAfterSave) this.prototypeEditorOpen = false
        } catch (error) {
            this.prototypeMessage = error?.message || 'Unable to save exercise.'
            console.error('Prototype exercise save failed', error)
        } finally {
            this.prototypeSaving = false
        }
    },
    cancelPrototypeExercise() {
        // The standalone prototype has no routine state to return to.
        // Discard local unsaved changes, restoring the last successful save.
        if (!window.confirm('Discard changes since your last save and close the editor?')) return
        this.restorePrototypeSaved()
        this.prototypeEditorOpen = false
        this.prototypeMessage = 'Changes discarded.'
    },
    reopenPrototypeExercise() {
        this.restorePrototypeSaved()
        this.prototypeEditorOpen = true
        this.prototypeMessage = ''
        this.$nextTick(() => this.updateTabEditorWidth())
    },
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
        name: 'Untitled exercise',
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
