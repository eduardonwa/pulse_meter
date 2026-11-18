import { parseMusicXmlTab } from './alphatab/musicxml-import.js'
import { serializeTabDraft, tabBeatUnits } from './alphatab/tab-editor.js'

// Beta-only editor state. Leave global Practice controls and persistence untouched.
export function tablatureRedesignBeta() {
    return {
    prototypeMessage: '',
    prototypeMessageType: 'success',
    redesignTransportStorageKey() {
        const index = this.tabEditorEditingIndex
        const step = index === null ? null : this.steps[index]
        const id = step?.id ?? (index === null ? 'draft' : 'local-' + index)
        return 'dorelog:tab-editor:transport-v1:' + id
    },
    prepareRedesignEditor() {
        this.prototypeMessage = ''
        this.prototypeMessageType = 'success'
        this.prototypePlaying = false
        this.prototypePlaybackLoading = false
        this.prototypePlaybackScore = null
        this.prototypePlayingBeatIndex = null
        this.prototypeProgress = 0
        this.prototypeDurationMs = 0
        this.prototypeCurrentMs = 0
        this.prototypeMusicXmlImport = null
        this.prototypeMusicXmlFullSource = null
        this.prototypeMusicXmlFileName = ''
        this.prototypeMusicXmlInitialBpm = null
        this.prototypeMusicXmlSelectedSection = 0
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
    prototypePlayingBeatIndex: null,
    // Approximate the destination immediately from the editable rhythm;
    // alphaTab corrects it using the actual MIDI tick after a seek.
    showPrototypeBeatAtPercent(percent) {
        if (this.tabFullMusicXml) { this.prototypePlayingBeatIndex = null; return }
        const beats = this.tabDraft.beats
        if (!beats.length) {
            this.prototypePlayingBeatIndex = null
            return
        }
        const lengths = beats.map(beat => tabBeatUnits(beat))
        const total = lengths.reduce((sum, length) => sum + length, 0)
        let position = Math.min(1 - Number.EPSILON, Math.max(0, percent / 100)) * total
        for (let index = 0; index < beats.length; index++) {
            position -= lengths[index]
            if (position < 0 || index === beats.length - 1) {
                this.prototypePlayingBeatIndex = index
                return
            }
        }
    },
    syncPrototypePlayedBeat(event) {
        if (event.detail?.element !== this.$refs.tabEditorPreview) return
        const measureIndex = Number(event.detail.measureIndex)
        const beatIndex = Number(event.detail.beatIndex)
        if (!Number.isInteger(measureIndex) || !Number.isInteger(beatIndex) ||
            measureIndex < 0 || beatIndex < 0) return
        const measures = this.tabEditorMeasureLayout()
        const beat = measures[measureIndex]?.notes?.[beatIndex]
        const next = Number.isInteger(beat?.index) ? beat.index : null
        if (this.prototypePlayingBeatIndex !== next) this.prototypePlayingBeatIndex = next
    },
    finishPrototypePlayback(event) {
        if (event.detail?.element !== this.$refs.tabEditorPreview) return
        this.prototypePlayingBeatIndex = null
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
        this.showPrototypeBeatAtPercent(percent)
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
        this.prototypePlayingBeatIndex = null
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
        this.prototypePlayingBeatIndex = null
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
    prototypeMusicXmlFileName: '',
    prototypeMusicXmlInitialBpm: null,
    prototypeMusicXmlSelectedSection: 0,
    async importPrototypeMusicXml(event) {
        const input = event.target
        const file = input.files?.[0]
        input.value = ''
        if (!file) return
        this.prototypeMusicXmlImport = null
        this.prototypeMusicXmlFullSource = null
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
            catch { this.prototypeMusicXmlImport = null }
            this.prototypeMusicXmlSelectedSection = 0
            this.prototypeMessageType = 'success'
            this.prototypeMessage = this.prototypeMusicXmlImport?.sections?.length
                ? 'Choose an editable section, or import the complete score for playback only.'
                : 'No editable sections found. The complete score can still be imported for playback.'
        } catch (error) {
            this.prototypeMusicXmlFullSource = null
            this.prototypeMusicXmlImport = null
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
        this.prototypeMusicXmlInitialBpm = null
        this.prototypeMessageType = 'success'
        this.prototypeMessage = 'Complete score imported for playback only. Save to keep it in your routine.'
        this.renderPrototypeFullScore()
    },
    applyPrototypeMusicXmlSection() {
        const imported = this.prototypeMusicXmlImport
        const section = imported?.sections?.[Number(this.prototypeMusicXmlSelectedSection)]
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
        this.prototypeMessage = 'Imported editable MusicXML section. Save to keep it in this routine.'
        this.$nextTick(() => this.updateTabEditorWidth())
    },

    }
}
