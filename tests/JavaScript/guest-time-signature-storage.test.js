import test from 'node:test'
import assert from 'node:assert/strict'
import { storage } from '../../resources/js/metronome/storage.js'

test('guest exercise retains a 13/4 time signature after reload', () => {
    const originalStorage = globalThis.localStorage
    const values = new Map()
    globalThis.localStorage = {
        getItem: key => values.get(key) ?? null,
        setItem: (key, value) => values.set(key, value),
    }
    try {
        const player = {
            ...storage(),
            steps: [{
                name: 'Exercise 4',
                bpm: 135,
                mode: 'drill',
                duration_seconds: 60,
                time_signature_numerator: 13,
                time_signature_denominator: 4,
            }],
        }
        player.saveToLocalStorage()
        player.steps = []
        player.loadFromLocalStorage()
        assert.equal(player.steps[0].time_signature_numerator, 13)
        assert.equal(player.steps[0].bpm, 135)
        assert.equal(player.steps[0].time_signature_denominator, 4)

        player.steps[0].time_signature_numerator = 17
        player.saveToLocalStorage()
        player.loadFromLocalStorage()
        assert.equal(player.steps[0].time_signature_numerator, 4)
    } finally {
        globalThis.localStorage = originalStorage
    }
})

test('guest import preserves supported 13/4 numerator', () => {
    const player = { ...storage(), maxSteps: 10 }
    const result = player.normalizeLocalRoutineImportSteps([{
        name: 'Exercise 4',
        bpm: 135,
        mode: 'drill',
        duration_seconds: 60,
        time_signature_numerator: 13,
        time_signature_denominator: 4,
    }])
    assert.equal(result?.[0]?.time_signature_numerator, 13)
})
