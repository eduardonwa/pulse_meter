import test from 'node:test'
import assert from 'node:assert/strict'

import {
    routineTemplateActions,
} from '../../resources/js/routine-template-player.js'

test('local template copy preserves alpha_tex', () => {
    let storedValue = null

    global.localStorage = {
        setItem(key, value) {
            assert.equal(
                key,
                'pulse_meter_routine'
            )

            storedValue = value
        },
    }

    const actions = routineTemplateActions()

    const context = {
        ...actions,

        steps: [
            {
                name: 'Alternate Picking',
                bpm: 160,
                mode: 'timer',
                duration_seconds: 60,

                alpha_tex:
                    '\\staff {tabs}\n\n:16\n5.1 6.1 7.1 8.1 9.1',
            },
        ],

        // Evitamos redirect en este test.
        $el: {
            closest() {
                return null
            },
        },
    }

    context.replaceLocalRoutineWithTemplate()

    const storedSteps = JSON.parse(storedValue)

    assert.equal(storedSteps.length, 1)

    assert.deepEqual(
        storedSteps[0],
        {
            name: 'Alternate Picking',
            bpm: 160,
            mode: 'drill',
            duration_seconds: 60,

            alpha_tex:
                '\\staff {tabs}\n\n:16\n5.1 6.1 7.1 8.1 9.1',
        }
    )
})

test('drill template copy preserves duration', () => {
    let storedValue
    global.localStorage = { setItem(_key, value) { storedValue = value } }
    const context = {
        ...routineTemplateActions(),
        steps: [{ name: 'Drill', bpm: 120, mode: 'drill', duration_seconds: 90 }],
        $el: { closest() { return null } },
    }
    context.replaceLocalRoutineWithTemplate()
    assert.deepEqual(JSON.parse(storedValue), [{
        name: 'Drill', bpm: 120, mode: 'drill', duration_seconds: 90, alpha_tex: null,
    }])
})
