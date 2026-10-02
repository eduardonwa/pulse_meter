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
        description: 'The major scale: a stable major sound with a natural 3rd, 6th and 7th.',
    },
    {
        id: 'dorian',
        label: 'Dorian',
        intervals: [1, 2, 'b3', 4, 5, 6, 'b7'],
        description: 'A minor mode with a natural 6th and a lowered 3rd and 7th.',
    },
    {
        id: 'phrygian',
        label: 'Phrygian',
        intervals: [1, 'b2', 'b3', 4, 5, 'b6', 'b7'],
        description: 'A minor mode with a lowered 2nd, 3rd, 6th and 7th.',
    },
    {
        id: 'lydian',
        label: 'Lydian',
        intervals: [1, 2, 3, '#4', 5, 6, 7],
        description: 'A major mode with a raised 4th degree.',
    },
    {
        id: 'mixolydian',
        label: 'Mixolydian',
        intervals: [1, 2, 3, 4, 5, 6, 'b7'],
        description: 'A major mode with a lowered 7th degree.',
    },
    {
        id: 'aeolian',
        label: 'minor (Aeolian)',
        intervals: [1, 2, 'b3', 4, 5, 'b6', 'b7'],
        description: 'The natural minor scale, with a lowered 3rd, 6th and 7th.',
    },
    {
        id: 'locrian',
        label: 'Locrian',
        intervals: [1, 'b2', 'b3', 4, 'b5', 'b6', 'b7'],
        description: 'A diminished-sounding mode with a lowered 2nd, 3rd, 5th, 6th and 7th.',
    },
    {
        id: 'harmonic-minor',
        label: 'harmonic minor',
        intervals: [1, 2, 'b3', 4, 5, 'b6', 7],
        description: 'A minor scale with a natural 7th, creating an augmented second between ♭6 and 7.',
    },
    {
        id: 'melodic-minor',
        label: 'melodic minor',
        intervals: [1, 2, 'b3', 4, 5, 6, 7],
        description: 'A minor scale with a natural 6th and 7th.',
    },
    {
        id: 'melodic-major',
        label: 'melodic major',
        intervals: [1, 2, 3, 4, 5, 'b6', 'b7'],
        description: 'A major scale with a lowered 6th and 7th.',
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

const NOTE_LETTERS = ['C', 'D', 'E', 'F', 'G', 'A', 'B']
const NATURAL_PITCH_CLASSES = {
    C: 0,
    D: 2,
    E: 4,
    F: 5,
    G: 7,
    A: 9,
    B: 11,
}
const MAJOR_INTERVAL_SEMITONES = {
    1: 0,
    2: 2,
    3: 4,
    4: 5,
    5: 7,
    6: 9,
    7: 11,
}

function normalizePitchClass(value) {
    return ((value % 12) + 12) % 12
}

function accidentalOffset(accidental = '') {
    return [...accidental].reduce((offset, character) => {
        if (character === 'b' || character === '♭') {
            return offset - 1
        }

        if (character === '#' || character === '♯') {
            return offset + 1
        }

        return offset
    }, 0)
}

function parseRoot(root) {
    const match = String(root).match(/^([A-G])([b#♭♯]*)$/)

    if (!match) {
        return null
    }

    return {
        letter: match[1],
        accidental: match[2],
    }
}

function getIntervalSemitones(interval) {
    const value = String(interval)
    const degree = Number(value.match(/\d+/)?.[0])
    const accidental = value.replace(/\d+/g, '')

    return MAJOR_INTERVAL_SEMITONES[degree]
        + accidentalOffset(accidental)
}

function formatAccidental(offset) {
    if (offset === 0) {
        return ''
    }

    if (offset > 0) {
        return '♯'.repeat(offset)
    }

    return '♭'.repeat(Math.abs(offset))
}

function formatInterval(interval) {
    return String(interval)
        .replaceAll('b', '♭')
        .replaceAll('#', '♯')
}

function formatNoteName(note) {
    return String(note)
        .replaceAll('b', '♭')
        .replaceAll('#', '♯')
}

function spellScaleNote(root, degreeIndex, semitoneOffset) {
    const parsedRoot = parseRoot(root)

    if (!parsedRoot) {
        return ''
    }

    const rootLetterIndex = NOTE_LETTERS.indexOf(parsedRoot.letter)
    const noteLetter = NOTE_LETTERS[
        (rootLetterIndex + degreeIndex) % NOTE_LETTERS.length
    ]

    const rootPitchClass = normalizePitchClass(
        NATURAL_PITCH_CLASSES[parsedRoot.letter]
        + accidentalOffset(parsedRoot.accidental)
    )

    const targetPitchClass = normalizePitchClass(
        rootPitchClass + semitoneOffset
    )
    const naturalPitchClass = NATURAL_PITCH_CLASSES[noteLetter]

    let difference = normalizePitchClass(
        targetPitchClass - naturalPitchClass
    )

    if (difference > 6) {
        difference -= 12
    }

    return `${noteLetter}${formatAccidental(difference)}`
}

function getStepLabel(semitones) {
    if (semitones === 1) {
        return 'H'
    }

    if (semitones === 2) {
        return 'W'
    }

    if (semitones === 3) {
        return 'W+H'
    }

    return `${semitones} st`
}

function buildScaleInfo(root, scaleDefinition) {
    const semitones = scaleDefinition.intervals.map(
        getIntervalSemitones
    )

    const notes = semitones.map((offset, index) => {
        return spellScaleNote(root, index, offset)
    })

    const formula = semitones.map((offset, index) => {
        const nextOffset =
            index === semitones.length - 1
                ? 12
                : semitones[index + 1]

        return getStepLabel(nextOffset - offset)
    })

    return {
        title: `${formatNoteName(root)} ${scaleDefinition.label}`,
        description: scaleDefinition.description,
        notes: [
            ...notes.map(formatNoteName),
            formatNoteName(root),
        ],
        degrees: [
            ...scaleDefinition.intervals.map(formatInterval),
            '8',
        ],
        formula: [...formula, '—'],
    }
}

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

            if (this.creativeMode === mode) {
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

        getRandomizerScaleInfo() {
            if (!this.randomizerResult) {
                return null
            }

            const scaleDefinition = RANDOMIZER_SCALES.find(
                item => item.id === this.randomizerResult.scale
            )

            if (!scaleDefinition) {
                return null
            }

            return buildScaleInfo(
                this.randomizerResult.root,
                scaleDefinition
            )
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
