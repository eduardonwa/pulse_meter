export function grouping() {
    return {
        isGroupingAccent(beat) {
            let groupStart = 1

            for (const groupSize of this.grouping) {
                if (beat === groupStart) {
                    return true
                }

                groupStart += groupSize
            }

            return false
        },

        getGroupingTotal(grouping = this.grouping) {
            return grouping.reduce((total, groupSize) => {
                return total + groupSize
            }, 0)
        },

        getGroupingRemaining(grouping = this.grouping) {
            return this.timeSignature.numerator
                - this.getGroupingTotal(grouping)
        },

        getGroupingFromPattern() {
            const groupStarts = []

            for (
                let beat = 1;
                beat <= this.timeSignature.numerator;
                beat++
            ) {
                if (this.pattern[beat - 1]?.groupStart) {
                    groupStarts.push(beat)
                }
            }

            const grouping = []

            for (let i = 0; i < groupStarts.length; i++) {
                const start = groupStarts[i]

                const nextStart =
                    groupStarts[i + 1]
                    ?? this.timeSignature.numerator + 1

                grouping.push(nextStart - start)
            }

            return grouping
        },

        setGrouping(grouping) {
            const remaining = this.getGroupingRemaining(grouping)

            if (remaining < 0) {
                return false
            }

            this.grouping = [...grouping]

            this.pattern = this.buildPatternFromGrouping(
                this.grouping
            )

            this.syncPulseDirty()

            return true
        },

        setGroupStart(beat, isGroupStart) {
            if (
                beat < 1
                || beat > this.timeSignature.numerator
            ) {
                return false
            }

            // El beat 1 siempre inicia el primer grupo.
            if (beat === 1 && !isGroupStart) {
                return false
            }

            const patternBeat = this.pattern[beat - 1]

            if (!patternBeat) {
                return false
            }

            // Estamos creando un nuevo group start.
            if (isGroupStart && !patternBeat.groupStart) {
                patternBeat.groupStart = true

                // Sonido default de un nuevo group start.
                patternBeat.sound = 'accent'
            } else {
                // Si lo quitamos, conservamos el sonido que ya tenía.
                patternBeat.groupStart = isGroupStart
            }

            this.grouping = this.getGroupingFromPattern()

            this.syncPulseDirty()

            return true
        },

        getGroupIndexForBeat(beat) {
            let groupIndex = -1

            for (let i = 0; i < beat; i++) {
                if (this.pattern[i]?.groupStart) {
                    groupIndex++
                }
            }

            return groupIndex
        },

        isBeatFilled(beat) {
            return beat <= this.getGroupingTotal()
        },
    }
}