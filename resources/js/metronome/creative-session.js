const CREATIVE_MODES = [
    'pulse-editor',
    'randomizer',
]

export const RANDOMIZER_KEYS = [
    'C',
    'Db',
    'D',
    'Eb',
    'E',
    'F',
    'Gb',
    'G',
    'Ab',
    'A',
    'Bb',
    'B',
]

export const RANDOMIZER_METERS = [
    {
        numerator: 2,
        denominator: 4,
        grouping: [2],
    },
    {
        numerator: 3,
        denominator: 4,
        grouping: [3],
    },
    {
        numerator: 4,
        denominator: 4,
        grouping: [4],
    },
    {
        numerator: 5,
        denominator: 4,
        grouping: [2, 3],
    },
    {
        numerator: 6,
        denominator: 8,
        grouping: [3, 3],
    },
    {
        numerator: 7,
        denominator: 8,
        grouping: [2, 2, 3],
    },
    {
        numerator: 9,
        denominator: 8,
        grouping: [3, 3, 3],
    },
    {
        numerator: 12,
        denominator: 8,
        grouping: [3, 3, 3, 3],
    },
]

function buildPulsePattern(grouping) {
    return grouping.flatMap(groupSize => {
        return Array.from(
            { length: groupSize },
            (_, index) => ({
                sound:
                    index === 0
                        ? 'accent'
                        : 'click',

                groupStart:
                    index === 0,
            })
        )
    })
}

export function creativeSession() {
    return {
        creativeMode: null,

        randomizerResult: null,

        randomizerPulse: {
            timeSignature: {
                numerator: 4,
                denominator: 4,
            },

            subdivision: 1,
            grouping: [4],
            pattern: buildPulsePattern([4]),
        },

        selectCreativeMode(mode) {
            if (!CREATIVE_MODES.includes(mode)) {
                return false
            }

            if (this.isPlaying) {
                this.stop('creative_mode_changed')
            }

            this.creativeMode = mode
            this.currentBeat = 1
            this.currentSubdivision = 0

            return true
        },

        getRandomItem(items, random = Math.random) {
            return items[
                Math.floor(random() * items.length)
            ]
        },

        getRandomBpm(random = Math.random) {
            const min = 60
            const max = 180

            return Math.floor(
                random() * (max - min + 1)
            ) + min
        },

        generateRandomIdea(random = Math.random) {
            const meter =
                this.getRandomItem(
                    RANDOMIZER_METERS,
                    random
                )

            const result = {
                bpm: this.getRandomBpm(random),
                key: this.getRandomItem(
                    RANDOMIZER_KEYS,
                    random
                ),

                timeSignature: {
                    numerator: meter.numerator,
                    denominator: meter.denominator,
                },
            }

            this.randomizerResult = result

            this.randomizerPulse = {
                timeSignature: {
                    ...result.timeSignature,
                },

                subdivision: 1,
                grouping: [...meter.grouping],
                pattern:
                    buildPulsePattern(
                        meter.grouping
                    ),
            }

            this.metronome.bpm = result.bpm
            this.currentBeat = 1
            this.currentSubdivision = 0

            if (this.isPlaying) {
                this.restartMetronome()
            }

            return result
        },

        getRandomizerResultLabel() {
            if (!this.randomizerResult) {
                return ''
            }

            const {
                bpm,
                key,
                timeSignature,
            } = this.randomizerResult

            return [
                `${bpm} BPM`,
                key,
                `${timeSignature.numerator}/${timeSignature.denominator}`,
            ].join(' · ')
        },
    }
}
