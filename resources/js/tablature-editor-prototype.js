import { Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm'
import { createTabDraft, TAB_STRINGS, serializeTabDraft, parseTabDraft } from './metronome/alphatab/tab-editor.js'
import { tablatureEditorMethods } from './metronome/tablature-editor-controller.js'
import { parseMusicXmlTab } from './metronome/alphatab/musicxml-import.js'

window.Alpine = Alpine

const PROTOTYPE_EXERCISE_KEY = 'dorelog:tab-editor-prototype:exercise-v1'

// Standalone UI harness. The real editor still owns persistence, import and playback.
Alpine.data('tablatureRedesign', () => ({
    tabDraft: createTabDraft(),
    prototypeEditorOpen: true,
    prototypeSaving: false,
    prototypeMessage: '',
    prototypeMessageType: 'success',
    prototypeSaved: null,
    prototypeMusicXmlImport: null,
    prototypeMusicXmlSelectedSection: 0,
    async importPrototypeMusicXml(event) {
        const input = event.target
        const file = input.files?.[0]
        input.value = ''
        if (!file) return
        this.prototypeMusicXmlImport = null
        if (!/\.(xml|musicxml)$/i.test(file.name) || file.size > 2_000_000) {
            this.prototypeMessageType = 'error'
            this.prototypeMessage = 'Choose an uncompressed .xml or .musicxml file under 2 MB.'
            return
        }
        try {
            const xml = await file.text()
            const imported = parseMusicXmlTab(xml)
            if (!imported.sections?.length) throw new Error('No editable tablature sections were found.')
            this.prototypeMusicXmlImport = imported
            this.prototypeMusicXmlSelectedSection = 0
            this.prototypeMessageType = 'success'
            this.prototypeMessage = 'Choose a MusicXML section to import.'
        } catch (error) {
            this.prototypeMessageType = 'error'
            this.prototypeMessage = error?.message || 'Unable to import MusicXML.'
            console.error('MusicXML import failed', error)
        }
    },
    applyPrototypeMusicXmlSection() {
        const imported = this.prototypeMusicXmlImport
        const section = imported?.sections?.[Number(this.prototypeMusicXmlSelectedSection)]
        if (!section) return
        // Keep the last saved exercise untouched until the user explicitly saves.
        this.recordTabEditorUndo()
        this.tabDraft = JSON.parse(JSON.stringify(section.draft))
        this.stepForm.name = imported.title.slice(0, 255)
        this.stepForm.bpm = section.bpm
        this.stepForm.time_signature_numerator = section.numerator
        this.stepForm.time_signature_denominator = section.denominator
        this.tabSelectedIndex = section.draft.beats.length
        this.tabSelectedIndices = []
        this.tabSelectionAnchor = null
        this.tabInsertAtBar = false
        this.prototypeMusicXmlImport = null
        this.prototypeMessageType = 'success'
        this.prototypeMessage = 'Imported editable MusicXML section. Save to keep it in this browser.'
        this.$nextTick(() => this.updateTabEditorWidth())
    },
    restorePrototypeSaved() {
        this.prototypeMusicXmlImport = null
        this.prototypeMusicXmlSelectedSection = 0
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
            this.prototypeMessageType = 'success'
            this.prototypeMessage = 'Loaded saved prototype exercise from this browser.'
        } catch (error) {
            this.prototypeMessageType = 'error'
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
            this.prototypeMessageType = 'success'
            this.prototypeMessage = 'Saved in this browser (prototype only; not added to a routine).'
            if (closeAfterSave) this.prototypeEditorOpen = false
        } catch (error) {
            this.prototypeMessageType = 'error'
            this.prototypeMessage = error?.message || 'Unable to save exercise.'
            console.error('Prototype exercise save failed', error)
        } finally {
            this.prototypeSaving = false
        }
    },
    cancelPrototypeExercise() {
        // The standalone prototype has no routine state to return to.
        // Discard local unsaved changes, restoring the last successful save.
        this.prototypeMessageType = 'success'
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
