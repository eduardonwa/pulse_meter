import assert from 'node:assert/strict'
import test from 'node:test'

import {
    pattern,
} from '../../resources/js/metronome/pulse-editor/pattern.js'

import {
    draft,
} from '../../resources/js/metronome/pulse-editor/draft.js'

import {
    grouping,
} from '../../resources/js/metronome/pulse-editor/grouping.js'


function createEditor() {
    const editor = {
        ...pattern(),
        ...grouping(),
        ...draft(),

        subdivision: 1,

        timeSignature: {
            numerator: 4,
            denominator: 4,
        },

        grouping: [4],

        pattern: [],

        currentBeat: 1,
        currentSubdivision: 0,

        intervalId: null,

        pulseBaseline: null,

        pulseDraft: {
            origin: 'new',
            sourceId: null,
            isDirty: false,
        },
    }

    editor.pattern =
        editor.buildPatternFromGrouping(
            editor.grouping
        )

    return editor
}


test(
    'eighth notes create an ampersand after every beat',
    () => {
        const editor = createEditor()

        editor.setSubdivision(2)

        assert.equal(
            editor.subdivision,
            2
        )

        for (const beat of editor.pattern) {
            assert.deepEqual(
                beat.subdivisions,
                [
                    {
                        label: '&',
                        sound: 'click',
                    },
                ]
            )
        }
    }
)


test(
    'sixteenth notes create e & a after every beat',
    () => {
        const editor = createEditor()

        editor.setSubdivision(4)

        for (const beat of editor.pattern) {
            assert.deepEqual(
                beat.subdivisions,
                [
                    {
                        label: 'e',
                        sound: 'click',
                    },
                    {
                        label: '&',
                        sound: 'click',
                    },
                    {
                        label: 'a',
                        sound: 'click',
                    },
                ]
            )
        }
    }
)


test(
    'a subdivision can have its own sound',
    () => {
        const editor = createEditor()

        editor.setSubdivision(4)

        editor.setPatternSubdivision(
            2,
            1,
            'accent'
        )

        assert.deepEqual(
            editor.pattern[1].subdivisions[1],
            {
                label: '&',
                sound: 'accent',
            }
        )
    }
)


test(
    'new beats inherit the active subdivision',
    async () => {
        const editor = createEditor()

        editor.setSubdivision(2)

        await editor.setDraftNumerator(5)

        assert.equal(
            editor.pattern.length,
            5
        )

        assert.deepEqual(
            editor.pattern[4].subdivisions,
            [
                {
                    label: '&',
                    sound: 'click',
                },
            ]
        )
    }
)


test(
    'pattern groups can be derived from another pulse',
    () => {
        const editor = createEditor()

        const groups = editor.getPatternGroups([
            {
                sound: 'accent',
                groupStart: true,
            },
            {
                sound: 'click',
                groupStart: false,
            },
            {
                sound: 'accent',
                groupStart: true,
            },
        ])

        assert.deepEqual(
            groups.map(group => {
                return group.map(item => item.beat)
            }),
            [
                [1, 2],
                [3],
            ]
        )
    }
)


test(
    'external pulse derives grouping from group starts',
    () => {
        const editor = createEditor()

        const pulse = {
            timeSignature: {
                numerator: 4,
                denominator: 4,
            },

            grouping: [4],

            pattern:
                editor.buildPatternFromGrouping([4]),
        }

        assert.equal(
            editor.setGroupStart(
                3,
                true,
                pulse
            ),
            true
        )

        assert.deepEqual(
            pulse.grouping,
            [2, 2]
        )

        assert.equal(
            pulse.pattern[2].sound,
            'accent'
        )

        assert.equal(
            editor.setGroupStart(
                1,
                false,
                pulse
            ),
            false
        )
    }
)
