import test from 'node:test'
import assert from 'node:assert/strict'

import {
    creativeSession,
    RANDOMIZER_METERS,
    RANDOMIZER_ROOTS,
    RANDOMIZER_SCALES,
} from '../../resources/js/metronome/creative-session.js'

test('creative session starts without a selected tool', () => {
    const session = creativeSession()

    assert.equal(session.creativeMode, null)
    assert.equal(
        session.randomizerPlaybackMode,
        'click'
    )
})

test('creative session selects supported tools', () => {
    const session = creativeSession()

    assert.equal(
        session.selectCreativeMode('pulse-editor'),
        true
    )
    assert.equal(session.creativeMode, 'pulse-editor')

    assert.equal(
        session.selectCreativeMode('randomizer'),
        true
    )
    assert.equal(session.creativeMode, 'randomizer')
})

test('creative session rejects unsupported tools', () => {
    const session = creativeSession()

    assert.equal(
        session.selectCreativeMode('unsupported'),
        false
    )
    assert.equal(session.creativeMode, null)
})

test('randomizer generates its minimum creative brief', () => {
    const session = creativeSession()

    session.metronome = {
        bpm: 100,
    }

    const result =
        session.generateRandomIdea(() => 0)

    assert.deepEqual(result, {
        bpm: 60,
        root: RANDOMIZER_ROOTS[0],
        scale: RANDOMIZER_SCALES[0].id,
        timeSignature: {
            numerator:
                RANDOMIZER_METERS[0].numerator,

            denominator:
                RANDOMIZER_METERS[0].denominator,
        },
    })

    assert.equal(session.metronome.bpm, 60)
    assert.equal(
        session.getRandomizerResultLabel(),
        '60 BPM · C major (Ionian) · 2/4'
    )
})

test('randomizer includes melodic major', () => {
    const melodicMajor =
        RANDOMIZER_SCALES.find(
            scale => scale.id === 'melodic-major'
        )

    assert.deepEqual(melodicMajor, {
        id: 'melodic-major',
        label: 'melodic major',
        intervals: [
            1,
            2,
            3,
            4,
            5,
            'b6',
            'b7',
        ],
    })
})

test('randomizer derives accents from default grouping', () => {
    const session = creativeSession()

    session.metronome = {
        bpm: 100,
    }

    const randomValues = [
        0.76,
        0.4,
        0,
        0.5,
    ]

    session.generateRandomIdea(
        () => randomValues.shift()
    )

    assert.deepEqual(
        session.randomizerPulse.grouping,
        [3, 3, 3]
    )

    assert.deepEqual(
        session.randomizerPulse.pattern
            .map(beat => beat.sound),

        [
            'accent',
            'click',
            'click',
            'accent',
            'click',
            'click',
            'accent',
            'click',
            'click',
        ]
    )
})

test('randomizer restarts active playback with the new result', () => {
    const session = creativeSession()

    session.metronome = {
        bpm: 100,
    }

    session.isPlaying = true
    session.restartCount = 0

    session.restartMetronome = () => {
        session.restartCount += 1
    }

    session.generateRandomIdea(() => 0)

    assert.equal(session.restartCount, 1)
})

test('randomizer adds subdivisions without changing its meter', () => {
    const session = creativeSession()

    session.metronome = {
        bpm: 100,
    }

    session.applySubdivisionToPattern = (
        pattern,
        subdivision
    ) => {
        const labels =
            subdivision === 2
                ? ['&']
                : []

        pattern.forEach(beat => {
            beat.subdivisions = labels.map(label => ({
                label,
                sound: 'click',
            }))
        })

        return [1, 2, 4].includes(subdivision)
    }

    session.generateRandomIdea(() => 0)

    const originalMeter = {
        ...session.randomizerPulse.timeSignature,
    }

    assert.equal(
        session.setRandomizerSubdivision(2),
        true
    )

    assert.deepEqual(
        session.randomizerPulse.timeSignature,
        originalMeter
    )

    assert.deepEqual(
        session.randomizerPulse
            .pattern[0]
            .subdivisions,
        [
            {
                label: '&',
                sound: 'click',
            },
        ]
    )
})

test('randomizer edits beat and subdivision sounds', () => {
    const session = creativeSession()

    session.metronome = {
        bpm: 100,
    }

    session.setPatternBeat = (
        beat,
        sound,
        pattern
    ) => {
        pattern[beat - 1].sound = sound
        return true
    }

    session.setPatternSubdivision = (
        beat,
        subdivisionIndex,
        sound,
        pattern
    ) => {
        pattern[beat - 1]
            .subdivisions[subdivisionIndex]
            .sound = sound

        return true
    }

    session.generateRandomIdea(() => 0)

    session.randomizerPulse
        .pattern[0]
        .subdivisions = [
            {
                label: '&',
                sound: 'click',
            },
        ]

    session.randomizerEditorTool = 'rest'
    session.cancelToolTether = () => {
        session.randomizerEditorTool = null
    }

    assert.equal(
        session.applyRandomizerTool(1),
        true
    )

    session.randomizerEditorTool = 'rest'

    assert.equal(
        session.applyRandomizerToolToSubdivision(
            1,
            0
        ),
        true
    )

    assert.equal(
        session.randomizerPulse.pattern[0].sound,
        'rest'
    )

    assert.equal(
        session.randomizerPulse
            .pattern[0]
            .subdivisions[0]
            .sound,
        'rest'
    )
})

test('randomizer edits group starts without changing meter', () => {
    const session = creativeSession()

    session.metronome = {
        bpm: 100,
    }

    session.generateRandomIdea(() => 0)

    session.getGroupingFromPattern = (
        pattern,
        numerator
    ) => {
        const starts = pattern
            .map((beat, index) => {
                return beat.groupStart
                    ? index + 1
                    : null
            })
            .filter(Boolean)

        return starts.map((start, index) => {
            return (
                starts[index + 1]
                ?? numerator + 1
            ) - start
        })
    }

    session.setGroupStart = (
        beat,
        isGroupStart,
        pulse
    ) => {
        pulse.pattern[beat - 1].groupStart =
            isGroupStart

        if (isGroupStart) {
            pulse.pattern[beat - 1].sound =
                'accent'
        }

        pulse.grouping =
            session.getGroupingFromPattern(
                pulse.pattern,
                pulse.timeSignature.numerator
            )

        return true
    }

    session.cancelToolTether = () => {
        session.randomizerEditorTool = null
    }

    const originalMeter = {
        ...session.randomizerPulse.timeSignature,
    }

    session.randomizerEditorTool = 'groupStart'

    assert.equal(
        session.applyRandomizerTool(2),
        true
    )

    assert.deepEqual(
        session.randomizerPulse.grouping,
        [1, 1]
    )

    assert.equal(
        session.randomizerPulse.pattern[1].sound,
        'accent'
    )

    assert.deepEqual(
        session.randomizerPulse.timeSignature,
        originalMeter
    )
})
