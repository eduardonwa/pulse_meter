export function grouping() {
    return {

        // GROUPING CALCULATIONS
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

        getGroupingFromPattern(
            pattern = this.pattern,
            numerator =
                this.timeSignature.numerator
        ) {
            const subdivision = this.getSubdivisionFromPattern(pattern)
            const groupStarts = []

            pattern.slice(0, numerator).forEach((beat, index) => {
                if (beat.groupStart) groupStarts.push(index * subdivision)

                beat.subdivisions?.forEach((item, subIndex) => {
                    if (item.groupStart) {
                        groupStarts.push(index * subdivision + subIndex + 1)
                    }
                })
            })

            const total = numerator * subdivision
            return groupStarts.map((start, index) => {
                const next = groupStarts[index + 1] ?? total
                return (next - start) / subdivision
            })
        },

        getGroupingLabel(
            pattern = this.pattern,
            numerator = this.timeSignature.numerator
        ) {
            const groups = this.getGroupingFromPattern(pattern, numerator)
            const subdivision = this.getSubdivisionFromPattern(pattern)

            return groups.some(value => !Number.isInteger(value))
                ? groups.map(value => Math.round(value * subdivision)).join(' + ')
                : groups.join(' + ')
        },

        // GROUPING UPDATES
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

        setGroupStart(
            beat,
            isGroupStart,
            pulse = null,
            subdivisionIndex = null
        ) {
            const pattern =
                pulse?.pattern
                ?? this.pattern

            const numerator =
                pulse?.timeSignature?.numerator
                ?? this.timeSignature.numerator

            if (
                beat < 1
                || beat > numerator
            ) {
                return false
            }

            // The first beat always starts the first group.
            if (beat === 1 && subdivisionIndex === null && !isGroupStart) {
                return false
            }

            const patternBeat = pattern[beat - 1]
            const item = subdivisionIndex === null
                ? patternBeat
                : patternBeat?.subdivisions?.[subdivisionIndex]

            if (!item || Boolean(item.groupStart) === isGroupStart) {
                return false
            }

            item.groupStart = isGroupStart
            if (isGroupStart) item.sound = 'accent'

            const grouping =
                this.getGroupingFromPattern(
                    pattern,
                    numerator
                )

            if (pulse) {
                pulse.grouping = grouping
            } else {
                this.grouping = grouping
                this.syncPulseDirty()
            }

            return true
        },

        // BEAT HELPERS
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

        getGroupSizeForBeat(beat) {
            const groupIndex =
                this.getGroupIndexForBeat(beat)

            if (groupIndex < 0) {
                return 0
            }

            return this.grouping[groupIndex] ?? 0
        },
    }
}
