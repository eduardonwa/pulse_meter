import test from 'node:test'
import assert from 'node:assert/strict'
import { loadPracticeModules } from '../../resources/js/practice-module-loader.js'

test('a failed AlphaTab chunk does not block Alpine startup on routine show', async () => {
    const calls = []
    await loadPracticeModules({
        loadMetronome: async () => { calls.push('metronome') },
        loadAlphaTab: async () => { throw new Error('AlphaTab chunk unavailable') },
        onOptionalFailure: (name) => calls.push('failed:' + name),
    })
    assert.ok(calls.includes('metronome'))
    assert.ok(calls.includes('failed:AlphaTab'))
})

test('the optional onboarding script is not required on routines/show', async () => {
    const calls = []
    await loadPracticeModules({
        loadMetronome: async () => { calls.push('metronome') },
        loadAlphaTab: async () => { calls.push('alphatab') },
    })
    assert.deepEqual(calls.sort(), ['alphatab', 'metronome'])
})

test('a broken onboarding chunk cannot block exercise controls', async () => {
    await assert.doesNotReject(() => loadPracticeModules({
        loadMetronome: async () => {},
        loadAlphaTab: async () => {},
        loadOnboarding: async () => { throw new Error('Tour unavailable') },
        onOptionalFailure: () => {},
    }))
})

test('a failed required metronome chunk still reports a bootstrap failure', async () => {
    await assert.rejects(() => loadPracticeModules({
        loadMetronome: async () => { throw new Error('Missing player') },
        loadAlphaTab: async () => {},
    }), /Missing player/)
})
