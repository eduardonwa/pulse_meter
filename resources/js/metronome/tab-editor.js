import { parseMusicXmlTab } from './alphatab/musicxml-import.js'
import { serializeTabDraft, tabBeatUnits } from './alphatab/tab-editor.js'

// Tab editor state. Leave global Practice controls and persistence untouched.
export function tabEditor() {
    return {
    prototypeMessage: '',
    prototypeMessageType: 'success',
    tabScoreView: 'grid',
    tabScorePreviewError: '',
    tabScoreRenderedKey: '',
    syncTabEditorScoreCaret() {
        const element = this.$refs?.tabEditorPreview
        if (!element || typeof window === 'undefined') return
        if (this.tabScoreView !== 'score' || this.tabFullMusicXml ||
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
        const measureIndex = measures.findIndex(measure =>
            measure.notes.some(note => note.index === anchorIndex))
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
    setTabScoreView(view) {
        if (!['grid', 'score'].includes(view) || this.tabScoreView === view) return
        this.tabScoreView = view
        this.$nextTick(() => {
            if (view === 'score') {
                if (!this.tabDraft.beats.length) {
                    // A new score starts with one editable measure.
                    this.tabDraft = { ...this.tabDraft, beats: [{ rest: true, duration: 1 }], breaks: [] }
                    this.selectTabEditorCell(0, this.tabDraft.string)
                } else if (this.tabSelectedIndex >= this.tabDraft.beats.length) {
                    this.selectTabEditorCell(this.tabDraft.beats.length - 1, this.tabDraft.string)
                }
                this.renderTabScorePreview()
                this.$refs.tabEditorScoreKeyboard?.focus()
            } else this.updateTabEditorWidth()
            this.syncTabEditorScoreCaret()
        })
    },
    renderTabScorePreview() {
        if (this.tabScoreView !== 'score' || this.tabFullMusicXml) return
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
        this.tabScoreView = 'grid'
        this.tabScorePreviewError = ''
        this.tabScoreRenderedKey = ''
        this.prototypeMessage = ''
        this.prototypeMessageType = 'success'
        this.prototypePlaying = false
        this.prototypePlaybackLoading = false
        this.prototypePlaybackScore = null
        this.prototypePlayingBeatIndex = null
        this.prototypeScoreStarted = false
        this.stopPrototypePlayheadAnimation()
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
    // Group neighboring selected rhythmic positions into a single visual block
    // per system; disconnected Ctrl/Cmd selections remain separate blocks.
    prototypeSelectionRuns(system) {
        if (this.tabSelectedIndices.length < 2) return []
        const selected = new Set(this.tabSelectedIndices)
        const runs = []
        let start = -1
        for (let i = 0; i <= system.length; i++) {
            const column = system[i]
            const included = column && !column.insertion && selected.has(column.index)
            if (included && start === -1) start = i
            if (!included && start !== -1) {
                runs.push({ start, length: i - start })
                start = -1
            }
        }
        return runs
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
    // Keep playback independent of the editing selection and count-in.
    prototypeScoreStarted: false,
    prototypePlayheadFrame: null,
    prototypePlayheadClock: null,
    prototypeBeatClock: null,
    stopPrototypePlayheadAnimation() {
        if (this.prototypePlayheadFrame !== null) {
            cancelAnimationFrame(this.prototypePlayheadFrame)
            this.prototypePlayheadFrame = null
        }
        this.prototypePlayheadClock = null
        this.prototypeBeatClock = null
        this.$refs.tabEditorDialog?.querySelectorAll('.tab-editor__playhead').forEach(el => {
            el.classList.remove('is-visible')
        })
    },
    paintPrototypePlayhead(progress) {
        if (!this.prototypeScoreStarted || this.tabFullMusicXml || !this.tabDraft.beats.length) return
        const beats = this.tabDraft.beats
        const units = beats.map(beat => tabBeatUnits(beat))
        const total = units.reduce((sum, unit) => sum + unit, 0)
        if (!(total > 0)) return
        let remaining = Math.max(0, Math.min(1, progress)) * total
        let index = beats.length - 1
        let fraction = 0
        for (let i = 0; i < beats.length; i++) {
            if (remaining < units[i] || i === beats.length - 1) {
                index = i
                fraction = Math.max(0, Math.min(1, remaining / units[i]))
                break
            }
            remaining -= units[i]
        }
        this.paintPrototypePlayheadBeat(index, fraction)
    },
    paintPrototypePlayheadBeat(index, fraction = 0) {
        if (!this.prototypeScoreStarted || this.tabFullMusicXml) return
        const grids = this.$refs.tabEditorScore?.querySelectorAll('.tab-editor__grid')
        grids?.forEach(grid => {
            const cursor = grid.querySelector('.tab-editor__playhead')
            const cell = grid.querySelector(`[data-playhead-beat="${index}"]`)
            if (!cursor) return
            if (!cell) {
                cursor.classList.remove('is-visible')
                return
            }
            const next = grid.querySelector(`[data-playhead-beat="${index + 1}"]`)
            const startX = cell.offsetLeft
            // The last note has no following cell, but it still owns one column.
            const endX = next?.offsetLeft ?? (startX + cell.offsetWidth)
            cursor.style.transform = `translate3d(${startX + (endX - startX) * Math.max(0, Math.min(1, fraction))}px, 0, 0)`
            cursor.classList.add('is-visible')
        })
    },
    startPrototypePlayheadAnimation() {
        if (!this.prototypeScoreStarted || this.tabFullMusicXml || this.prototypePlayheadFrame !== null) return
        const frame = now => {
            this.prototypePlayheadFrame = null
            if (!this.prototypePlaying || this.tabFullMusicXml) return
            const beatClock = this.prototypeBeatClock
            if (beatClock && beatClock.duration > 0) {
                // Each beat notification is an audio-timed anchor. This avoids
                // stretching three notes over a four-beat bar's trailing rest.
                const fraction = (now - beatClock.at) / beatClock.duration
                this.paintPrototypePlayheadBeat(beatClock.index, fraction)
            } else {
                const clock = this.prototypePlayheadClock
                if (clock && clock.duration > 0) {
                    const elapsed = Math.max(0, now - clock.at)
                    const position = Math.min(clock.duration, clock.current + elapsed)
                    this.paintPrototypePlayhead(position / clock.duration)
                }
            }
            this.prototypePlayheadFrame = requestAnimationFrame(frame)
        }
        this.prototypePlayheadFrame = requestAnimationFrame(frame)
    },
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
        if (next !== null && this.prototypeScoreStarted) {
            const beatUnits = tabBeatUnits(this.tabDraft.beats[next])
            const bpm = Number(this.stepForm.bpm)
            if (Number.isFinite(bpm) && bpm > 0) {
                this.prototypeBeatClock = {
                    index: next,
                    at: performance.now(),
                    duration: beatUnits * 15000 / bpm,
                }
                this.paintPrototypePlayheadBeat(next, 0)
            }
        }
    },
    finishPrototypePlayback(event) {
        if (event.detail?.element !== this.$refs.tabEditorPreview) return
        this.prototypePlaying = false
        this.prototypeScoreStarted = false
        this.stopPrototypePlayheadAnimation()
        this.prototypePlayingBeatIndex = null
    },
    startPrototypeScorePlayhead(event) {
        if (event.detail?.element !== this.$refs.tabEditorPreview || this.tabFullMusicXml) return
        this.prototypeScoreStarted = true
        this.prototypePlayheadClock = {
            current: this.prototypeCurrentMs, duration: this.prototypeDurationMs, at: performance.now(),
        }
        // Position on the actual starting beat if alphaTab emitted it before
        // the score-start signal; otherwise use the seek/progress fallback.
        if (Number.isInteger(this.prototypePlayingBeatIndex)) {
            const index = this.prototypePlayingBeatIndex
            const bpm = Number(this.stepForm.bpm)
            if (Number.isFinite(bpm) && bpm > 0 && this.tabDraft.beats[index]) {
                this.prototypeBeatClock = {
                    index,
                    at: performance.now(),
                    duration: tabBeatUnits(this.tabDraft.beats[index]) * 15000 / bpm,
                }
                this.paintPrototypePlayheadBeat(index, 0)
            }
        } else {
            this.paintPrototypePlayhead(this.prototypeProgress / 100)
        }
        if (this.prototypePlaying) this.startPrototypePlayheadAnimation()
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
        this.prototypePlayheadClock = { current: this.prototypeCurrentMs, duration, at: performance.now() }
        // Do not display a playhead for the idle editor or count-in.
        // Keep its final position visible when the score is paused.
        if (!this.prototypePlaying && this.prototypeScoreStarted) {
            this.paintPrototypePlayhead(this.prototypeProgress / 100)
        }
    },
    previewPrototypeSeek(value) {
        if ((!this.tabDraft.beats.length && !this.tabFullMusicXml) || this.prototypePlaybackLoading) return
        const percent = Math.max(0, Math.min(100, Number(value) || 0))
        this.prototypeProgress = percent
        this.prototypeCurrentMs = percent / 100 * this.prototypeDurationMs
        this.showPrototypeBeatAtPercent(percent)
        this.prototypePlayheadClock = { current: this.prototypeCurrentMs, duration: this.prototypeDurationMs, at: performance.now() }
        this.prototypeBeatClock = null
        if (this.prototypeScoreStarted) this.paintPrototypePlayhead(percent / 100)
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
        this.prototypeScoreStarted = false
        this.stopPrototypePlayheadAnimation()
        this.prototypeProgress = 0
        this.prototypeDurationMs = 0
        this.prototypeCurrentMs = 0
    },
    syncPrototypePlayback(event) {
        if (event.detail?.element !== this.$refs.tabEditorPreview) return
        this.prototypePlaying = Boolean(event.detail.playing)
        this.prototypePlaybackLoading = false
        if (this.prototypePlaying) {
            this.prototypePlayheadClock = {
                current: this.prototypeCurrentMs, duration: this.prototypeDurationMs, at: performance.now(),
            }
            // alphaTab also reports Playing during count-in.
            if (this.prototypeScoreStarted) this.startPrototypePlayheadAnimation()
        } else {
            if (this.prototypePlayheadFrame !== null) cancelAnimationFrame(this.prototypePlayheadFrame)
            this.prototypePlayheadFrame = null
            this.prototypePlayheadClock = null
            this.prototypeBeatClock = null
        }
    },
    prototypePlaybackError(event) {
        if (event.detail?.element !== this.$refs.tabEditorPreview) return
        this.prototypePlaying = false
        this.prototypePlaybackLoading = false
        this.prototypePlaybackScore = null
        this.prototypePlayingBeatIndex = null
        this.prototypeScoreStarted = false
        this.stopPrototypePlayheadAnimation()
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
        this.prototypeScoreStarted = false
        this.stopPrototypePlayheadAnimation()
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
        this.$nextTick(() => this.updateTabEditorWidth())
    },

    }
}
