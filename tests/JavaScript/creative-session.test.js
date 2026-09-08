import test from 'node:test'
import assert from 'node:assert/strict'

import {
    creativeSession,
    RANDOMIZER_KEYS,
    RANDOMIZER_METERS,
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
        key: RANDOMIZER_KEYS[0],
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
        '60 BPM · C · 2/4'
    )
})

test('randomizer derives accents from default grouping', () => {
    const session = creativeSession()

    session.metronome = {
        bpm: 100,
    }

    const randomValues = [
        0.76,
        0.5,
        0.4,
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
