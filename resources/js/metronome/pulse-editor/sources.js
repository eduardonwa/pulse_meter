// ¿De dónde salió el pattern que estoy editando?
export function sources() {
    return {
        async setTimeSignature(signature) {
            this.timeSignature = {
                numerator: signature.numerator,
                denominator: signature.denominator,
            }

            this.grouping = [...signature.grouping]

            this.pattern = this.buildPatternFromGrouping(
                this.grouping
            )

            this.pulseDraft = {
                origin: 'preset',
                sourceId: signature.id,
                isDirty: false,
            }

            this.currentBeat = 1
            this.editorTool = null

            this.setPulseBaseline()

            if (this.intervalId) {
                await this.startMetronome(this.metronome.bpm)
            }
        },

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

            this.pulseDraft = {
                origin: 'new',
                sourceId: null,
                isDirty: false,
            }

            this.currentBeat = 1

            this.setPulseBaseline()
        },

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

        selectPulseSource(value) {
            if (value === 'new') {
                this.startNewPattern()
                return
            }

            const [origin, id] = value.split(':')

            if (origin === 'preset') {
                const preset = this.timeSignatures.find(
                    signature => signature.id === id
                )

                if (!preset) { return }

                this.setTimeSignature(preset)

                return
            }

            if (origin === 'user') {
                this.loadUserPattern(Number(id))
            }
        },

        getCurrentPulseSourceLabel() {
            if (this.pulseDraft.origin === 'new') {
                return 'New Pattern'
            }

            if (this.pulseDraft.origin === 'preset') {
                const preset = this.timeSignatures.find(
                    item => item.id === this.pulseDraft.sourceId
                )

                if (!preset) {
                    return 'Pattern'
                }

                return `${preset.numerator}/${preset.denominator} — ${preset.grouping.join(' + ')}`
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