import assert from 'node:assert/strict'
import test from 'node:test'
import { readFileSync } from 'node:fs'
import { lifecycle } from '../../resources/js/metronome/lifecycle.js'

test('practice panel is marked ready after the first Alpine render without waiting for server requests', async () => {
    const previousRaf = globalThis.requestAnimationFrame
    globalThis.requestAnimationFrame = callback => { callback(); return 1 }

    let releasePatterns
    const patterns = new Promise(resolve => { releasePatterns = resolve })
    const root = { dataset: {} }
    const session = Object.assign(lifecycle(), {
        $root: root,
        $nextTick(callback) { callback() },
        activeTab: 'exercises',
        usesLocalPersistence: false,
        usesServerPersistence: true,
        loadRecentSessions() {},
        loadClickSounds() {},
        loadPulsePatterns() { return patterns },
        loadRandomizedSessions() { return Promise.resolve() },
        restorePulseSource() { return Promise.resolve() },
        setPulseBaseline() {},
    })

    try {
        const initializing = session.init()
        assert.equal(root.dataset.practiceReady, 'true')
        releasePatterns()
        await initializing
    } finally {
        globalThis.requestAnimationFrame = previousRaf
    }
})

test('server-rendered loading state is present before Alpine starts', () => {
    const markup = readFileSync(
        new URL('../../resources/views/components/metronome/panel.blade.php', import.meta.url),
        'utf8',
    )
    assert.match(markup, /class="practice-loading" role="status"/)
    assert.match(markup, /class="practice-panel-content"/)
    assert.match(markup, /Loading your exercises/)
})
