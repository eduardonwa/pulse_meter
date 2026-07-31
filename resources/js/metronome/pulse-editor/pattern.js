export function pattern() {
    return {

        // PATTERN CREATION
        buildPatternFromGrouping(grouping = this.grouping) {
            const pattern = []

            for (const groupSize of grouping) {
                for (let i = 0; i < groupSize; i++) {
                    pattern.push({
                        sound: i === 0
                            ? 'accent'
                            : 'click',

                        groupStart: i === 0,
                    })
                }
            }

            return pattern
        },

        // BEAT EDITING
        setPatternBeat(beat, type) {
            const allowedTypes = [
                'accent',
                'click',
                'rest',
            ]

            if (!allowedTypes.includes(type)) {
                return false
            }

            if (
                beat < 1
                || beat > this.timeSignature.numerator
            ) {
                return false
            }

            const patternBeat = this.pattern[beat - 1]

            if (!patternBeat) {
                return false
            }

            patternBeat.sound = type

            this.syncPulseDirty()

            return true
        },

        cyclePatternBeat(beat) {
            const current = this.pattern[beat - 1]

            const nextType = {
                accent: 'click',
                click: 'rest',
                rest: 'accent',
            }

            this.setPatternBeat(
                beat,
                nextType[current?.sound] ?? 'accent'
            )
        },

        // PATTERN GROUPING
        getPatternGroups() {
            const groups = []
            let currentGroup = []

            this.pattern.forEach((item, index) => {
                if (
                    item.groupStart
                    && currentGroup.length
                ) {
                    groups.push(currentGroup)
                    currentGroup = []
                }

                currentGroup.push({
                    ...item,
                    beat: index + 1,
                })
            })

            if (currentGroup.length) {
                groups.push(currentGroup)
            }

            return groups
        },
    }
}