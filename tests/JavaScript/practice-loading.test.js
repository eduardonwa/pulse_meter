import assert from 'node:assert/strict'
import test from 'node:test'
import { readFileSync } from 'node:fs'
import { lifecycle } from '../../resources/js/metronome/lifecycle.js'

test('practice panel is marked ready after the first Alpine render without waiting for server requests', async () => {
    const previousRaf = globalThis.requestAnimationFrame
    globalThis.requestAnimationFrame = callback => { callback(); return 1 }

    let releasePatterns
    const patterns = new Promise(resolve => { releasePatterns = resolve })
    const removed = []
    const style = { removeProperty(name) { removed.push(name) } }
    let loadingRemoved = false
    const root = {
        dataset: {},
        querySelector(selector) {
            if (selector === '[data-practice-content]') return { style }
            if (selector === '[data-practice-loading]') return {
                remove() { loadingRemoved = true },
            }
            return null
        },
    }
    const originalWindow = globalThis.window
    globalThis.window = new EventTarget()
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
        assert.equal(loadingRemoved, true)
        assert.deepEqual(removed, ['visibility', 'height', 'overflow', 'pointer-events'])
        releasePatterns()
        await initializing
    } finally {
        globalThis.requestAnimationFrame = previousRaf
        globalThis.window = originalWindow
    }
})

test('server-rendered loading state is present before Alpine starts', () => {
    const markup = readFileSync(
        new URL('../../resources/views/components/metronome/panel.blade.php', import.meta.url),
        'utf8',
    )
    assert.match(markup, /class="practice-loading" role="status"/)
    assert.match(markup, /data-practice-content style="visibility: hidden;/)
    assert.match(markup, /data-practice-loading/)
    assert.match(markup, /Loading your exercises/)
})
