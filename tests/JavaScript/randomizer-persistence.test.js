import assert from 'node:assert/strict'
import test from 'node:test'

import {
    randomizerPersistence,
} from '../../resources/js/metronome/randomizer-persistence.js'

function createSession() {
    return {
        ...randomizerPersistence(),

        randomizerResult: {
            bpm: 118,
            root: 'B',
            scale: 'lydian',
            timeSignature: {
                numerator: 3,
                denominator: 4,
            },
        },

        randomizerPulse: {
            timeSignature: {
                numerator: 3,
                denominator: 4,
            },
            subdivision: 2,
            grouping: [2, 1],
            pattern: [
                {
                    sound: 'accent',
                    groupStart: true,
                    subdivisions: [
                        {
                            label: '&',
                            sound: 'click',
                        },
                    ],
                },
                {
                    sound: 'click',
                    groupStart: false,
                    subdivisions: [
                        {
                            label: '&',
                            sound: 'rest',
                        },
                    ],
                },
                {
                    sound: 'accent',
                    groupStart: true,
                    subdivisions: [
                        {
                            label: '&',
                            sound: 'click',
                        },
                    ],
                },
            ],
        },

        randomizerPlaybackMode: 'pulse',
        metronome: {
            bpm: 124,
        },

        getRandomizerResultLabel() {
            return '124 BPM · B Lydian · 3/4'
        },
    }
}

test('randomizer persistence serializes the complete session', () => {
    const session = createSession()

    assert.deepEqual(
        session.getRandomizerSessionPayload(),
        {
            name: '124 BPM · B Lydian · 3/4',
            bpm: 124,
            root: 'B',
            scale: 'lydian',
            playback_mode: 'pulse',
            numerator: 3,
            denominator: 4,
            subdivision: 2,
            grouping: [2, 1],
            pattern: session.randomizerPulse.pattern,
        }
    )
})

test('loading a saved session restores independent rhythm state', () => {
    const session = createSession()

    const saved = {
        id: 7,
        name: 'Odd B Lydian',
        bpm: 96,
        root: 'B',
        scale: 'lydian',
        playbackMode: 'click',
        timeSignature: {
            numerator: 3,
            denominator: 4,
        },
        subdivision: 1,
        grouping: [3],
        pattern: [
            {
                sound: 'accent',
                groupStart: true,
            },
            {
                sound: 'click',
                groupStart: false,
            },
            {
                sound: 'rest',
                groupStart: false,
            },
        ],
    }

    session.randomizerSavedSessions = [saved]
    session.closeRandomizerSessionsDialog = () => {}
    session.isPlaying = false
    session.currentBeat = 3
    session.currentSubdivision = 1

    assert.equal(
        session.loadRandomizedSession(7),
        true
    )

    assert.equal(session.metronome.bpm, 96)
    assert.equal(
        session.randomizerPlaybackMode,
        'click'
    )
    assert.deepEqual(
        session.randomizerPulse.grouping,
        [3]
    )
    assert.deepEqual(session.randomizerDraft, {
        origin: 'saved',
        sourceId: 7,
    })

    session.randomizerPulse.pattern[0].sound =
        'rest'

    assert.equal(
        saved.pattern[0].sound,
        'accent'
    )
})

test('saving a generated session tracks the returned record', async () => {
    const session = createSession()

    session.showToast = () => {}
    session.storeRandomizedSession = async () => ({
        id: 9,
        name: 'Saved idea',
    })

    assert.equal(
        await session.saveRandomizedSession(),
        true
    )

    assert.equal(
        session.randomizerSavedSessions.length,
        1
    )
    assert.deepEqual(session.randomizerDraft, {
        origin: 'saved',
        sourceId: 9,
    })
})
