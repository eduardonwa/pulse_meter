export function sources() {
    return {
        // PRESET LOADING
        async loadPreset(preset) {
            this.timeSignature = {
                numerator: preset.numerator,
                denominator: preset.denominator,
            }

            this.grouping = [
                ...preset.grouping
            ]

            this.pattern = preset.pattern.map(beat => ({
                ...beat,
            }))

            this.patternName = preset.name

            this.pulseDraft = {
                origin: 'preset',
                sourceId: preset.id,
                isDirty: false,
            }

            this.currentBeat = 1
            this.editorTool = null

            this.setPulseBaseline()

            if (this.intervalId) {
                await this.startMetronome(
                    this.metronome.bpm
                )
            }
        },

        // NEW PATTERN
        startNewPattern() {
            this.cancelToolTether()

            this.timeSignature = {
                numerator: 4,
                denominator: 4,
            }

            // Desde cero = un solo grupo inicialmente.
            this.grouping = [4]

            this.pattern = this.buildPatternFromGrouping(
                this.grouping
            )

            this.patternName = ''

            this.pulseDraft = {
                origin: 'new',
                sourceId: null,
                isDirty: false,
            }

            this.currentBeat = 1

            this.setPulseBaseline()
        },

        // USER PATTERN LOADING
        loadUserPattern(id) {
            const userPattern = this.userPatterns.find(
                pattern => pattern.id === id
            )

            if (!userPattern) {
                return false
            }

            this.timeSignature = {
                ...userPattern.timeSignature
            }

            this.grouping = [
                ...userPattern.grouping
            ]

            this.pattern = userPattern.pattern.map(beat => ({
                ...beat,
            }))

            this.patternName = userPattern.name

            this.pulseDraft = {
                origin: 'user',
                sourceId: userPattern.id,
                isDirty: false,
            }

            this.currentBeat = 1
            this.editorTool = null

            this.setPulseBaseline()

            return true
        },

        // SOURCE SELECTION
        selectPulseSource(value) {
            if (value === 'new') {
                this.startNewPattern()
                return
            }

            const [origin, id] = value.split(':')

            if (origin === 'preset') {
                const presetId = Number(id)

                const preset = this.pulsePresets.find(
                    preset => preset.id === presetId
                )

                if (!preset) {
                    return
                }

                this.loadPreset(preset)

                return
            }

            if (origin === 'user') {
                this.loadUserPattern(Number(id))
            }
        },

        // SOURCE LABEL
        getCurrentPulseSourceLabel() {
            if (this.pulseDraft.origin === 'new') {
                return 'New Pattern'
            }

            if (this.pulseDraft.origin === 'preset') {
                const preset = this.pulsePresets.find(
                    item => item.id === this.pulseDraft.sourceId
                )

                if (!preset) { return 'Pattern' }

                return `${preset.name} (${preset.numerator}/${preset.denominator})`
            }

            if (this.pulseDraft.origin === 'user') {
                const pattern = this.userPatterns.find(
                    item => item.id === this.pulseDraft.sourceId
                )

                return pattern?.name ?? 'Pattern'
            }

            return 'Pattern'
        },
    }
}