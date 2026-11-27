import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { patchAlphaTabMetronome } from '../../scripts/patch-alphatab-metronome.mjs'

test('native metronome accents beat zero and preserves regular clicks', () => {
    const source = readFileSync(new URL('../../public/alphatab/alphaTab.core.mjs', import.meta.url), 'utf8')
    const patched = patchAlphaTabMetronome(source)
    const expression = patched.match(/this\.channelNoteOn\(this\._metronomeChannel, SynthConstants\.MetronomeKey, (\(m\.event\?\.metronomeNumerator === 0 \? 127 : 40\) \/ 127)\);/)[1]
    const velocity = new Function('m', 'return ' + expression)
    assert.equal(velocity({ event: { metronomeNumerator: 0 } }), 1)
    for (const beat of [1, 2, 3, 4, 5, 6]) {
        assert.equal(velocity({ event: { metronomeNumerator: beat } }), 40 / 127)
    }
    assert.equal(velocity({}), 40 / 127)
})

test('patch refuses an unknown synthesizer implementation', () => {
    assert.throws(() => patchAlphaTabMetronome('unknown'), /implementation changed/)
})
