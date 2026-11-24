import { parseMusicXmlTab } from './alphatab/musicxml-import.js'
import { serializeTabDraft, tabBeatUnits } from './alphatab/tab-editor.js'

// Tab editor state. Leave global Practice controls and persistence untouched.
export function tabEditor() {
    return {
    prototypeMessage: '',
    prototypeMessageType: 'success',
    tabScorePreviewError: '',
    tabScoreRenderedKey: '',
    syncTabEditorScoreCaret() {
        const element = this.$refs?.tabEditorPreview
        if (!element || typeof window === 'undefined') return
        if (this.tabFullMusicXml ||
            !this.tabDraft.beats.length) {
            window.dispatchEvent(new CustomEvent('alphatab:editor-caret', {
                detail: { element, selection: null },
            }))
            return
        }
        const count = this.tabDraft.beats.length
        const insertion = Boolean(this.tabInsertAtBar || this.tabSelectedIndex >= count)
        const measures = this.tabEditorMeasureLayout()
        const index = Math.max(0, Math.min(this.tabSelectedIndex, count - 1))
        const previousMeasure = insertion && this.tabSelectedIndex > 0
            ? measures.find(measure => measure.endIndex === this.tabSelectedIndex - 1)
            : null
        const fitsPrevious = previousMeasure &&
            previousMeasure.used + tabBeatUnits(this.tabDraft) <= previousMeasure.capacity + 1e-7
        const anchorIndex = fitsPrevious ? this.tabSelectedIndex - 1 : index
        const measureIndex = this.tabEditorActiveMeasureIndex(measures)
        if (measureIndex < 0) return
        const beatIndex = measures[measureIndex].notes.findIndex(note => note.index === anchorIndex)
        const after = Boolean(fitsPrevious)
        const newMeasure = insertion && this.tabSelectedIndex >= count && !after
        const selected = new Set(this.tabSelectedIndices)
        const range = selected.size > 1 ? measures.flatMap((measure, m) =>
            measure.notes.flatMap((note, b) => selected.has(note.index)
                ? [{ measureIndex: m, beatIndex: b }] : [])) : []
        window.dispatchEvent(new CustomEvent('alphatab:editor-caret', {
            detail: { element, selection: {
                measureIndex, beatIndex, insertion, after, newMeasure, range, string: this.tabDraft.string,
            } },
        }))
    },
    initializeTabScoreView() {
        if (!this.tabDraft.beats.length) {
            this.tabDraft = { ...this.tabDraft, beats: [{ rest: true, duration: 1 }], breaks: [] }
            this.selectTabEditorCell(0, this.tabDraft.string)
        } else if (this.tabSelectedIndex >= this.tabDraft.beats.length) {
            this.selectTabEditorCell(this.tabDraft.beats.length - 1, this.tabDraft.string)
        }
        this.renderTabScorePreview()
        this.$refs.tabEditorScoreKeyboard?.focus({ preventScroll: true })
        this.syncTabEditorScoreCaret()
    },
    renderTabScorePreview() {
        if (this.tabFullMusicXml) return
        const element = this.$refs.tabEditorPreview
        if (!element) return
        this.tabScorePreviewError = ''
        if (!this.tabDraft.beats.length) return
        let alphaTex
        try {
            alphaTex = serializeTabDraft(this.tabDraft,
                this.stepForm.time_signature_numerator,
                Number(this.stepForm.bpm),
                this.stepForm.time_signature_denominator)
        } catch (error) {
            this.tabScorePreviewError = error?.message || 'Unable to render this tablature.'
            return
        }
        const renderKey = [alphaTex, this.stepForm.bpm, this.tabDraft.instrument].join('|')
        if (renderKey === this.tabScoreRenderedKey) return
        this.tabScoreRenderedKey = renderKey
        // Only structural edits need a new rendering. Clicking a note
        // also touches the draft selection, but should not redraw the page.
        this.prototypePlaybackScore = null
        window.dispatchEvent(new CustomEvent('alphatab:preview-draft', {
            detail: { element, alphaTex, bpm: Number(this.stepForm.bpm),
                instrument: this.tabDraft.instrument, renderOnly: true },
        }))
    },
    redesignTransportStorageKey() {
        const index = this.tabEditorEditingIndex
        const step = index === null ? null : this.steps[index]
        const id = step?.id ?? (index === null ? 'draft' : 'local-' + index)
        return 'dorelog:tab-editor:transport-v1:' + id
    },
    prepareRedesignEditor() {
        this.tabScorePreviewError = ''
        this.tabScoreRenderedKey = ''
        this.prototypeMessage = ''
        this.prototypeMessageType = 'success'
        this.prototypePlaying = false
        this.prototypePlaybackLoading = false
        this.prototypePlaybackScore = null
        this.prototypeProgress = 0
        this.prototypeDurationMs = 0
        this.prototypeCurrentMs = 0
        this.prototypeMusicXmlImport = null
        this.prototypeMusicXmlFullSource = null
        this.prototypeMusicXmlIssue = ''
        this.prototypeMusicXmlFileName = ''
        this.prototypeMusicXmlInitialBpm = null
        this.prototypeLoop = false
        this.prototypeCountIn = false
        this.prototypeMetronome = false
        this.loadPrototypeTransportOptions()
    },
    prototypePlaying: false,
    prototypeLoop: false,
    prototypeCountIn: false,
    prototypeMetronome: false,
    loadPrototypeTransportOptions() {
        try {
            const stored = window.localStorage.getItem(this.redesignTransportStorageKey())
            if (!stored) return
            const options = JSON.parse(stored)
            if (!options || typeof options !== 'object' || Array.isArray(options)) return
            if (typeof options.loop === 'boolean') this.prototypeLoop = options.loop
            if (typeof options.countIn === 'boolean') this.prototypeCountIn = options.countIn
            if (typeof options.metronome === 'boolean') this.prototypeMetronome = options.metronome
        } catch (error) {
            console.warn('Could not load prototype transport preferences', error)
        }
    },
    savePrototypeTransportOptions() {
        try {
            window.localStorage.setItem(this.redesignTransportStorageKey(), JSON.stringify(this.prototypeTransportOptions()))
        } catch (error) {
            console.warn('Could not save prototype transport preferences', error)
        }
    },
    prototypeTransportOptions() {
        return { loop: this.prototypeLoop, countIn: this.prototypeCountIn, metronome: this.prototypeMetronome }
    },
    setPrototypeTransportOption(option) {
        const key = { loop: 'prototypeLoop', countIn: 'prototypeCountIn', metronome: 'prototypeMetronome' }[option]
        if (!key) return
        this[key] = !this[key]
        this.savePrototypeTransportOptions()
        window.dispatchEvent(new CustomEvent('alphatab:editor-transport-options', {
            detail: { element: this.$refs.tabEditorPreview, options: this.prototypeTransportOptions() },
        }))
    },
    prototypePlaybackLoading: false,
    prototypePlaybackScore: null,
    finishPrototypePlayback(event) {
        if (event.detail?.element !== this.$refs.tabEditorPreview) return
        this.prototypePlaying = false
    },
    prototypeProgress: 0,
    prototypeDurationMs: 0,
    prototypeCurrentMs: 0,
    syncPrototypePlaybackPosition(event) {
        if (event.detail?.element !== this.$refs.tabEditorPreview) return
        const duration = Number(event.detail.endTime)
        const current = Number(event.detail.currentTime)
        if (!Number.isFinite(duration) || duration <= 0 || !Number.isFinite(current)) return
        this.prototypeDurationMs = duration
        this.prototypeCurrentMs = Math.max(0, Math.min(duration, current))
        this.prototypeProgress = this.prototypeCurrentMs / duration * 100
    },
    previewPrototypeSeek(value) {
        if ((!this.tabDraft.beats.length && !this.tabFullMusicXml) || this.prototypePlaybackLoading) return
        const percent = Math.max(0, Math.min(100, Number(value) || 0))
        this.prototypeProgress = percent
        this.prototypeCurrentMs = percent / 100 * this.prototypeDurationMs
    },
    seekPrototypePlayback(value) {
        if ((!this.tabDraft.beats.length && !this.tabFullMusicXml) || this.prototypePlaybackLoading) return
        this.previewPrototypeSeek(value)
        window.dispatchEvent(new CustomEvent('alphatab:seek', {
            detail: { element: this.$refs.tabEditorPreview, percent: this.prototypeProgress },
        }))
    },
    stopPrototypePlayback() {
        const element = this.$refs.tabEditorPreview
        if (element) window.dispatchEvent(new CustomEvent('alphatab:preview-draft', {
            detail: { element, play: false },
        }))
        this.prototypePlaying = false
        this.prototypePlaybackLoading = false
        this.prototypePlaybackScore = null
        this.prototypeProgress = 0
        this.prototypeDurationMs = 0
        this.prototypeCurrentMs = 0
    },
    syncPrototypePlayback(event) {
        if (event.detail?.element !== this.$refs.tabEditorPreview) return
        this.prototypePlaying = Boolean(event.detail.playing)
        this.prototypePlaybackLoading = false
    },
    prototypePlaybackError(event) {
        if (event.detail?.element !== this.$refs.tabEditorPreview) return
        this.prototypePlaying = false
        this.prototypePlaybackLoading = false
        this.prototypePlaybackScore = null
        this.prototypeMessageType = 'error'
        this.prototypeMessage = event.detail.message || 'Unable to play this tablature.'
    },
    togglePrototypePlayback() {
        if (this.prototypePlaybackLoading) return
        const element = this.$refs.tabEditorPreview
        if (!element) return
        let alphaTex
        try {
            alphaTex = this.tabFullMusicXml ? null : serializeTabDraft(this.tabDraft, this.stepForm.time_signature_numerator,
                Number(this.stepForm.bpm), this.stepForm.time_signature_denominator)
        } catch (error) {
            this.prototypeMessageType = 'error'
            this.prototypeMessage = error?.message || 'Unable to play this tablature.'
            return
        }
        const source = this.tabFullMusicXml ?? alphaTex
        if (source === this.prototypePlaybackScore) {
            window.dispatchEvent(new CustomEvent('alphatab:play-pause', { detail: { element } }))
            return
        }
        this.prototypePlaybackLoading = true
        const seekPercent = this.prototypeProgress
        this.prototypeDurationMs = 0
        this.prototypeCurrentMs = 0
        this.prototypePlaybackScore = source
        window.dispatchEvent(new CustomEvent('alphatab:preview-draft', {
            detail: { element, alphaTex, musicXml: this.tabFullMusicXml, bpm: Number(this.stepForm.bpm),
                instrument: this.tabDraft.instrument, seekPercent,
                transportOptions: this.prototypeTransportOptions(), play: true },
        }))
    },
    prototypeMusicXmlImport: null,
    prototypeMusicXmlFullSource: null,
    prototypeMusicXmlIssue: '',
    canImportPrototypeMusicXmlForEditing() {
        const imported = this.prototypeMusicXmlImport
        return Boolean(imported?.sections?.length === 1 && !imported.warnings?.length &&
            imported.sections[0].firstMeasure === 1)
    },
    prototypeMusicXmlHasMultipleTimeSignatures() {
        const sections = this.prototypeMusicXmlImport?.sections ?? []
        return new Set(sections.map(section => `${section.numerator}/${section.denominator}`)).size > 1
    },
    dismissPrototypeMusicXml() {
        this.prototypeMusicXmlImport = null
        this.prototypeMusicXmlFullSource = null
        this.prototypeMusicXmlIssue = ''
    },
    prototypeMusicXmlFileName: '',
    prototypeMusicXmlInitialBpm: null,
    async importPrototypeMusicXml(event) {
        const input = event.target
        const file = input.files?.[0]
        input.value = ''
        if (!file) return
        this.$refs.tabEditorImportDetails?.removeAttribute('open')
        this.prototypeMusicXmlImport = null
        this.prototypeMusicXmlFullSource = null
        this.prototypeMusicXmlIssue = ''
        if (!/\.(xml|musicxml)$/i.test(file.name) || file.size > 2_000_000) {
            this.prototypeMessageType = 'error'
            this.prototypeMessage = 'Choose an uncompressed .xml or .musicxml file under 2 MB.'
            return
        }
        try {
            const xml = await file.text()
            if (/<!ENTITY|<!DOCTYPE[^>]*\[/i.test(xml)) throw new Error('MusicXML with custom entities is not supported')
            const doc = new DOMParser().parseFromString(xml, 'application/xml')
            if (doc.getElementsByTagName('parsererror').length ||
                !['score-partwise', 'score-timewise'].includes(doc.documentElement?.localName)) {
                throw new Error('Invalid MusicXML score')
            }
            this.prototypeMusicXmlFullSource = xml
            this.prototypeMusicXmlFileName = file.name.replace(/\.(musicxml|xml)$/i, '')
            const bpm = Number(doc.querySelector('sound[tempo]')?.getAttribute('tempo') ??
                doc.querySelector('metronome per-minute')?.textContent)
            this.prototypeMusicXmlInitialBpm = Number.isFinite(bpm) && bpm >= 30 && bpm <= 400 ? Math.round(bpm) : null
            try { this.prototypeMusicXmlImport = parseMusicXmlTab(xml) }
            catch (error) {
                this.prototypeMusicXmlImport = null
                this.prototypeMusicXmlIssue = error?.message || 'The editor cannot read this notation.'
            }
            this.prototypeMessageType = 'success'
            this.prototypeMessage = this.canImportPrototypeMusicXmlForEditing()
                ? 'This tablature is ready to edit.'
                : 'This score can be imported for playback only.'
        } catch (error) {
            this.prototypeMusicXmlFullSource = null
            this.prototypeMusicXmlImport = null
            this.prototypeMusicXmlIssue = ''
            this.prototypeMessageType = 'error'
            this.prototypeMessage = error?.message || 'Unable to import MusicXML.'
        }
    },
    renderPrototypeFullScore() {
        const element = this.$refs.tabEditorPreview
        if (!element || !this.tabFullMusicXml) return
        this.$nextTick(() => {
            window.dispatchEvent(new CustomEvent('alphatab:preview-draft', {
                detail: { element, musicXml: this.tabFullMusicXml, bpm: Number(this.stepForm.bpm),
                    transportOptions: this.prototypeTransportOptions(), renderOnly: true },
            }))
        })
    },
    applyPrototypeMusicXmlFullScore() {
        if (!this.prototypeMusicXmlFullSource) return
        this.stopPrototypePlayback()
        this.recordTabEditorUndo()
        this.tabFullMusicXml = this.prototypeMusicXmlFullSource
        this.tabDraft = { ...this.tabDraft, beats: [], breaks: [] }
        if (this.prototypeMusicXmlInitialBpm) this.stepForm.bpm = this.prototypeMusicXmlInitialBpm
        this.stepForm.name = (this.prototypeMusicXmlImport?.title || this.prototypeMusicXmlFileName || 'Imported score').slice(0, 255)
        this.tabSelectedIndices = []
        this.tabSelectedIndex = 0
        this.tabInsertAtBar = false
        this.prototypeMusicXmlImport = null
        this.prototypeMusicXmlFullSource = null
        this.prototypeMusicXmlIssue = ''
        this.prototypeMusicXmlInitialBpm = null
        this.prototypeMessageType = 'success'
        this.prototypeMessage = 'Complete score imported for playback only. Save to keep it in your routine.'
        this.renderPrototypeFullScore()
    },
    applyPrototypeMusicXmlSection() {
        if (!this.canImportPrototypeMusicXmlForEditing()) return
        const imported = this.prototypeMusicXmlImport
        const section = imported.sections[0]
        if (!section) return
        // Keep the last saved exercise untouched until the user explicitly saves.
        this.stopPrototypePlayback()
        this.recordTabEditorUndo()
        this.tabDraft = JSON.parse(JSON.stringify(section.draft))
        this.tabFullMusicXml = null
        this.prototypeMusicXmlFullSource = null
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
        this.prototypeMessage = 'Imported tablature for editing. Save to keep it in this routine.'
        this.$nextTick(() => this.initializeTabScoreView())
    },

    }
}
