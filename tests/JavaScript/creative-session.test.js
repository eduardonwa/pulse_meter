import test from 'node:test'
import assert from 'node:assert/strict'

import {
    creativeSession,
} from '../../resources/js/metronome/creative-session.js'

test('creative session starts without a selected tool', () => {
    const session = creativeSession()

    assert.equal(session.creativeMode, null)
})

test('creative session selects supported tools', () => {
    const session = creativeSession()

    assert.equal(
        session.selectCreativeMode('pulse-editor'),
        true
    )
    assert.equal(session.creativeMode, 'pulse-editor')

    assert.equal(
        session.selectCreativeMode('randomizer'),
        true
    )
    assert.equal(session.creativeMode, 'randomizer')
})

test('creative session rejects unsupported tools', () => {
    const session = creativeSession()

    assert.equal(
        session.selectCreativeMode('unsupported'),
        false
    )
    assert.equal(session.creativeMode, null)
})
