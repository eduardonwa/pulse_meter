import assert from 'node:assert/strict'
import test from 'node:test'
import { bootstrap } from '../../resources/js/bootstrap.js'

test('practice page waits for both registrations before starting Livewire', async () => {
    const sequence = []
    let completeLoad
    const pending = new Promise(resolve => { completeLoad = resolve })
    const initializing = bootstrap({
        needsPractice: true,
        loadPractice: async () => {
            sequence.push('load practice')
            await pending
            sequence.push('practice registered')
        },
        startLivewire: () => sequence.push('start Livewire'),
    })
    await Promise.resolve()
    assert.deepEqual(sequence, ['load practice'])
    completeLoad()
    await initializing
    assert.deepEqual(sequence, ['load practice', 'practice registered', 'start Livewire'])
})

test('ordinary pages start Livewire without loading practice', async () => {
    const sequence = []
    await bootstrap({
        needsPractice: false,
        loadPractice: () => { sequence.push('unexpected') },
        startLivewire: () => sequence.push('start Livewire'),
    })
    assert.deepEqual(sequence, ['start Livewire'])
})

test('failed loading never initializes Alpine with missing practice factories', async () => {
    let started = false
    await assert.rejects(bootstrap({
        needsPractice: true,
        loadPractice: () => Promise.reject(new Error('asset unavailable')),
        startLivewire: () => { started = true },
    }), /asset unavailable/)
    assert.equal(started, false)
})
