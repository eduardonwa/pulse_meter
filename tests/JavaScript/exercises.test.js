import assert from 'node:assert/strict'
import test from 'node:test'

import {
    exercises,
} from '../../resources/js/metronome/exercises.js'

global.window = new EventTarget()

global.CustomEvent = class CustomEvent extends Event {
    constructor(type, options = {}) {
        super(type)

        this.detail = options.detail
    }
}

function createEditingSession() {
    const session = exercises()

    const calls = {
        startMetronome: 0,
        stop: 0,
    }

    Object.assign(session, {
        steps: [
            {
                id: 1,
                name: 'Exercise 1',
                bpm: 100,
                mode: 'timer',
                duration_seconds: 60,
                origin: 'custom',
            },
        ],

        stepFormMode: 'edit',
        stepFormIndex: 0,

        stepForm: {
            name: 'Exercise 1',
            bpm: 100,
            mode: 'timer',
            duration_seconds: 60,
        },

        stepFormMinutes: 1,
        stepFormSeconds: 0,

        stepFormInitial: {
            name: 'Exercise 1',
            bpm: 100,
            mode: 'timer',
            duration_seconds: 60,
        },

        activeExerciseIndex: 0,
        isPlaying: true,
        usesServerPersistence: false,

        metronome: {
            bpm: 100,
        },

        startMetronome(bpm) {
            calls.startMetronome++
            this.metronome.bpm = bpm
        },

        stop(reason) {
            calls.stop++
            this.stopReason = reason
            this.isPlaying = false
        },

        saveToLocalStorage() {},
        track() {},
        closeStepFormModal() {},
        resetStepForm() {},
        $nextTick() {},
    })

    return {
        session,
        calls,
    }
}

test('changing the mode of the active exercise stops playback', async () => {
    const { session, calls } = createEditingSession()

    session.stepForm.mode = 'classic'

    await session.saveStepForm()

    assert.equal(calls.stop, 1)
    assert.equal(calls.startMetronome, 0)
    assert.equal(session.stopReason, 'exercise_changed')
    assert.equal(session.steps[0].mode, 'classic')
})

test('changing the duration of the active exercise stops playback', async () => {
    const { session, calls } = createEditingSession()

    session.stepFormMinutes = 2

    await session.saveStepForm()

    assert.equal(calls.stop, 1)
    assert.equal(calls.startMetronome, 0)
    assert.equal(session.stopReason, 'exercise_changed')
    assert.equal(session.steps[0].duration_seconds, 120)
})

test('renaming the active exercise does not interrupt playback', async () => {
    const { session, calls } = createEditingSession()

    session.stepForm.name = 'Warm Up'

    await session.saveStepForm()

    assert.equal(calls.stop, 0)
    assert.equal(calls.startMetronome, 0)
    assert.equal(session.isPlaying, true)
    assert.equal(session.steps[0].name, 'Warm Up')
})

test('changing only the BPM updates active playback', async () => {
    const { session, calls } = createEditingSession()

    session.stepForm.bpm = 140

    await session.saveStepForm()

    assert.equal(calls.stop, 0)
    assert.equal(calls.startMetronome, 1)
    assert.equal(session.metronome.bpm, 140)
    assert.equal(session.steps[0].bpm, 140)
})

test('changing BPM rerenders an already mounted idle AlphaTab exercise', () => {
    const session = exercises()

    Object.assign(session, {
        steps: [
            {
                id: 1,
                name: 'Exercise 1',
                bpm: 100,
                mode: 'timer',
                duration_seconds: 60,
                origin: 'preset',
                alpha_tex: '3.3 5.3',
            },
        ],
        currentIndex: 0,
        activeExerciseIndex: null,
        isPlaying: false,
        usesServerPersistence: false,
        saveToLocalStorage() {},
        trackDebounced() {},
    })

    let received = null

    window.addEventListener(
        'alphatab:set-bpm',
        event => {
            received = event.detail
        },
        { once: true }
    )

    session.updateExerciseBpm(0, 90)

    assert.deepEqual(received, {
        index: 0,
        bpm: 90,
        renderTempo: true,
    })
    assert.equal(session.steps[0].bpm, 90)
})
