import test from 'node:test'
import assert from 'node:assert/strict'

import { compose } from '../../resources/js/metronome/compose.js'
import { exercises } from '../../resources/js/metronome/exercises.js'
import { state } from '../../resources/js/metronome/state.js'
import { timerDuration } from '../../resources/js/metronome/timer-duration.js'


test('state consumes Pro exercise limits from practice context', () => {
    const appState = state({
        limits: {
            exercise_count: 20,
            exercise_duration_seconds: 900,
        },
    })

    assert.equal(
        appState.maxSteps,
        20
    )

    assert.equal(
        appState.maxExerciseDurationSeconds,
        900
    )
})

test('state falls back to Free exercise limits', () => {
    const appState = state()

    assert.equal(
        appState.maxSteps,
        10
    )

    assert.equal(
        appState.maxExerciseDurationSeconds,
        300
    )
})

test('exercise behavior preserves the configured Pro count limit', () => {
    const appState = compose(
        state({
            limits: {
                exercise_count: 20,
                exercise_duration_seconds: 900,
            },
        }),
        exercises()
    )

    assert.equal(
        appState.maxSteps,
        20
    )
})

test('Pro exercise form exposes minutes through fifteen', () => {
    const appState = compose(
        state({
            limits: {
                exercise_count: 20,
                exercise_duration_seconds: 900,
            },
        }),
        exercises()
    )

    assert.equal(
        appState.stepFormMinutesOptions[0],
        0
    )

    assert.equal(
        appState.stepFormMinutesOptions.at(-1),
        15
    )

    assert.equal(
        appState.stepFormMinutesOptions.length,
        16
    )
})

test('Free exercise form exposes minutes through five', () => {
    const appState = compose(
        state(),
        exercises()
    )

    assert.equal(
        appState.stepFormMinutesOptions.at(-1),
        5
    )

    assert.equal(
        appState.stepFormMinutesOptions.length,
        6
    )
})

test('exercise form prevents seconds beyond the duration limit', () => {
    const appState = compose(
        state({
            limits: {
                exercise_count: 20,
                exercise_duration_seconds: 900,
            },
        }),
        exercises()
    )

    appState.stepFormMinutes = 15

    assert.deepEqual(
        appState.stepFormSecondsOptions,
        [0]
    )

    appState.stepFormMinutes = 14

    assert.equal(
        appState.stepFormSecondsOptions.at(-1),
        59
    )
})

test('exercise form normalizes seconds at the maximum minute', () => {
    const appState = compose(
        state({
            limits: {
                exercise_count: 20,
                exercise_duration_seconds: 900,
            },
        }),
        exercises()
    )

    appState.stepFormMinutes = 14
    appState.stepFormSeconds = 59

    appState.stepFormMinutes = 15
    appState.normalizeStepFormDuration()

    assert.equal(
        appState.stepFormMinutes,
        15
    )

    assert.equal(
        appState.stepFormSeconds,
        0
    )

    assert.equal(
        appState
            .getStepFormPayload()
            .duration_seconds,
        900
    )
})

test('Pro session timer exposes minutes through fifteen', () => {
    const appState = compose(
        state({
            limits: {
                exercise_count: 20,
                exercise_duration_seconds: 900,
            },
        }),
        timerDuration()
    )

    assert.equal(
        appState.timerMinutesOptions.at(-1),
        15
    )

    assert.equal(
        appState.timerMinutesOptions.length,
        16
    )
})

test('Free session timer exposes minutes through five', () => {
    const appState = compose(
        state(),
        timerDuration()
    )

    assert.equal(
        appState.timerMinutesOptions.at(-1),
        5
    )

    assert.equal(
        appState.timerMinutesOptions.length,
        6
    )
})

test('Pro session timer clamps duration at fifteen minutes', () => {
    const appState = compose(
        state({
            limits: {
                exercise_count: 20,
                exercise_duration_seconds: 900,
            },
        }),
        timerDuration()
    )

    appState.metronome.duration_seconds = 959

    appState.clampMetronomeDuration()

    assert.equal(
        appState.metronome.duration_seconds,
        900
    )
})

test('Free session timer clamps duration at five minutes', () => {
    const appState = compose(
        state(),
        timerDuration()
    )

    appState.metronome.duration_seconds = 359

    appState.clampMetronomeDuration()

    assert.equal(
        appState.metronome.duration_seconds,
        300
    )
})

test('session timer seconds stop at the plan maximum minute', () => {
    const proState = compose(
        state({
            limits: {
                exercise_count: 20,
                exercise_duration_seconds: 900,
            },
        }),
        timerDuration()
    )

    proState.metronomeMinutes = 15

    assert.deepEqual(
        proState.timerSecondsOptions,
        [0]
    )

    proState.metronomeMinutes = 14

    assert.equal(
        proState.timerSecondsOptions.at(-1),
        59
    )

    const freeState = compose(
        state(),
        timerDuration()
    )

    freeState.metronomeMinutes = 5

    assert.deepEqual(
        freeState.timerSecondsOptions,
        [0]
    )
})