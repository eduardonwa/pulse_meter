export function draft() {
    return {
        getPulseSnapshot() {
            return {
                timeSignature: {
                    ...this.timeSignature,
                },

                grouping: [
                    ...this.grouping,
                ],

                pattern: this.pattern.map(beat => ({
                    ...beat,
                })),
            }
        },

        syncPulseDirty() {
            if (!this.pulseBaseline) {
                this.pulseDraft.isDirty = false
                return
            }

            const current =
                this.getPulseSnapshot()

            this.pulseDraft.isDirty =
                JSON.stringify(current)
                !== JSON.stringify(this.pulseBaseline)
        },

        setPulseBaseline() {
            this.pulseBaseline =
                this.getPulseSnapshot()

            this.pulseDraft.isDirty = false
        },

        async setDraftMeter(numerator, denominator) {
            const parsedNumerator = Number(numerator)
            const parsedDenominator = Number(denominator)

            if (
                !Number.isInteger(parsedNumerator)
                || parsedNumerator < 1
            ) {
                return false
            }

            if (
                ![2, 4, 8, 16].includes(parsedDenominator)
            ) {
                return false
            }

            this.timeSignature = {
                numerator: parsedNumerator,
                denominator: parsedDenominator,
            }

            this.grouping = [
                parsedNumerator,
            ]

            this.pattern = this.buildPatternFromGrouping(
                this.grouping
            )

            this.currentBeat = 1

            this.syncPulseDirty()

            if (this.intervalId) {
                await this.startMetronome(
                    this.metronome.bpm
                )
            }

            return true
        },

        async setDraftNumerator(numerator) {
            const value = Number(numerator)

            if (
                !Number.isInteger(value)
                || value < 1
            ) {
                return false
            }

            const currentLength = this.pattern.length

            if (value > currentLength) {
                for (
                    let beat = currentLength;
                    beat < value;
                    beat++
                ) {
                    this.pattern.push({
                        sound: 'click',
                        groupStart: false,
                    })
                }
            }

            if (value < currentLength) {
                this.pattern = this.pattern.slice(
                    0,
                    value
                )
            }

            this.timeSignature.numerator = value

            this.grouping =
                this.getGroupingFromPattern()

            this.currentBeat = Math.min(
                this.currentBeat,
                value
            )

            this.syncPulseDirty()

            if (this.intervalId) {
                await this.startMetronome(
                    this.metronome.bpm
                )
            }

            return true
        },

        async setDraftDenominator(denominator) {
            const value = Number(denominator)

            if (![2, 4, 8, 16].includes(value)) {
                return false
            }

            this.timeSignature.denominator = value

            this.syncPulseDirty()

            if (this.intervalId) {
                await this.startMetronome(
                    this.metronome.bpm
                )
            }

            return true
        },
    }
}