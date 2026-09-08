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
        setPatternBeat(
            beat,
            type,
            pattern = this.pattern
        ) {
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
                || beat > pattern.length
            ) {
                return false
            }

            const patternBeat = pattern[beat - 1]

            if (!patternBeat) {
                return false
            }

            patternBeat.sound = type

            if (pattern === this.pattern) {
                this.syncPulseDirty()
            }

            return true
        },

        setPatternSubdivision(
            beat,
            subdivisionIndex,
            type,
            pattern = this.pattern
        ) {
            const allowedTypes = [
                'accent',
                'click',
                'rest',
            ]

            if (!allowedTypes.includes(type)) {
                return false
            }

            const patternBeat = pattern[beat - 1]

            if (!patternBeat) {
                return false
            }

            const subdivision =
                patternBeat.subdivisions?.[subdivisionIndex]

            if (!subdivision) {
                return false
            }

            subdivision.sound = type

            if (pattern === this.pattern) {
                this.syncPulseDirty()
            }

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
        getPatternGroups(pattern = this.pattern) {
            const groups = []
            let currentGroup = []

            pattern.forEach((item, index) => {
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

        // SUBDIVISIONS
        getSubdivisionLabels(
            subdivision = this.subdivision
        ) {
            if (subdivision === 2) {
                return ['&']
            }

            if (subdivision === 4) {
                return ['e', '&', 'a']
            }

            return []
        },

        setSubdivision(value) {
            const subdivision = Number(value)

            if (![1, 2, 4].includes(subdivision)) {
                return false
            }

            this.subdivision = subdivision

            this.applySubdivisionToPattern(
                this.pattern,
                subdivision
            )

            this.syncPulseDirty()

            return true
        },

        applySubdivisionToPattern(
            pattern,
            subdivision
        ) {
            if (![1, 2, 4].includes(subdivision)) {
                return false
            }

            const labels =
                this.getSubdivisionLabels(subdivision)

            pattern.forEach(beat => {
                beat.subdivisions = labels.map(label => ({
                    label,
                    sound: 'click',
                }))
            })

            return true
        },

        getSubdivisionFromPattern(pattern = this.pattern) {
            const subdivisionCount =
                pattern[0]?.subdivisions?.length ?? 0

            if (subdivisionCount === 1) {
                return 2
            }

            if (subdivisionCount === 3) {
                return 4
            }

            return 1
        },
    }
}
