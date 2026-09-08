const CREATIVE_MODES = [
    'pulse-editor',
    'randomizer',
]

export const RANDOMIZER_ROOTS = [
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

export const RANDOMIZER_SCALES = [
    {
        id: 'ionian',
        label: 'major (Ionian)',
        intervals: [1, 2, 3, 4, 5, 6, 7],
    },
    {
        id: 'dorian',
        label: 'Dorian',
        intervals: [1, 2, 'b3', 4, 5, 6, 'b7'],
    },
    {
        id: 'phrygian',
        label: 'Phrygian',
        intervals: [1, 'b2', 'b3', 4, 5, 'b6', 'b7'],
    },
    {
        id: 'lydian',
        label: 'Lydian',
        intervals: [1, 2, 3, '#4', 5, 6, 7],
    },
    {
        id: 'mixolydian',
        label: 'Mixolydian',
        intervals: [1, 2, 3, 4, 5, 6, 'b7'],
    },
    {
        id: 'aeolian',
        label: 'minor (Aeolian)',
        intervals: [1, 2, 'b3', 4, 5, 'b6', 'b7'],
    },
    {
        id: 'locrian',
        label: 'Locrian',
        intervals: [1, 'b2', 'b3', 4, 'b5', 'b6', 'b7'],
    },
    {
        id: 'harmonic-minor',
        label: 'harmonic minor',
        intervals: [1, 2, 'b3', 4, 5, 'b6', 7],
    },
    {
        id: 'melodic-minor',
        label: 'melodic minor',
        intervals: [1, 2, 'b3', 4, 5, 6, 7],
    },
    {
        id: 'melodic-major',
        label: 'melodic major',
        intervals: [1, 2, 3, 4, 5, 'b6', 'b7'],
    },
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
        randomizerEditorTool: null,
        randomizerPlaybackMode: 'click',

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

            if (
                mode === 'pulse-editor'
                && typeof this.$nextTick === 'function'
            ) {
                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        window.dispatchEvent(
                            new Event('picker:sync')
                        )
                    })
                })
            }

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

            const root =
                this.getRandomItem(
                    RANDOMIZER_ROOTS,
                    random
                )

            const scale =
                this.getRandomItem(
                    RANDOMIZER_SCALES,
                    random
                )

            const result = {
                bpm: this.getRandomBpm(random),
                root,
                scale: scale.id,

                timeSignature: {
                    numerator: meter.numerator,
                    denominator: meter.denominator,
                },
            }

            this.randomizerResult = result
            this.randomizerEditorTool = null
            this.randomizerSessionName = ''
            this.randomizerDraft = {
                origin: 'generated',
                sourceId: null,
            }

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

        setRandomizerSubdivision(value) {
            const subdivision = Number(value)

            if (
                !this.applySubdivisionToPattern(
                    this.randomizerPulse.pattern,
                    subdivision
                )
            ) {
                return false
            }

            this.randomizerPulse.subdivision =
                subdivision

            if (this.isPlaying) {
                this.restartMetronome()
            }

            return true
        },

        applyRandomizerTool(beat) {
            if (!this.randomizerEditorTool) {
                return false
            }

            if (
                this.randomizerEditorTool
                === 'groupStart'
            ) {
                const patternBeat =
                    this.randomizerPulse
                        .pattern[beat - 1]

                if (!patternBeat) {
                    return false
                }

                const applied = this.setGroupStart(
                    beat,
                    !patternBeat.groupStart,
                    this.randomizerPulse
                )

                this.cancelToolTether()

                return applied
            }

            const applied = this.setPatternBeat(
                beat,
                this.randomizerEditorTool,
                this.randomizerPulse.pattern
            )

            if (applied) {
                this.cancelToolTether()
            }

            return applied
        },

        applyRandomizerToolToSubdivision(
            beat,
            subdivisionIndex
        ) {
            if (!this.randomizerEditorTool) {
                return false
            }

            const applied =
                this.setPatternSubdivision(
                beat,
                subdivisionIndex,
                this.randomizerEditorTool,
                this.randomizerPulse.pattern
            )

            if (applied) {
                this.cancelToolTether()
            }

            return applied
        },

        getRandomizerResultLabel() {
            if (!this.randomizerResult) {
                return ''
            }

            const {
                root,
                scale,
                timeSignature,
            } = this.randomizerResult

            const bpm = Number(
                this.metronome?.bpm
                ?? this.randomizerResult.bpm
            )

            const scaleLabel =
                RANDOMIZER_SCALES.find(
                    item => item.id === scale
                )?.label
                ?? scale

            return [
                `${bpm} BPM`,
                `${root} ${scaleLabel}`,
                `${timeSignature.numerator}/${timeSignature.denominator}`,
            ].join(' · ')
        },
    }
}
