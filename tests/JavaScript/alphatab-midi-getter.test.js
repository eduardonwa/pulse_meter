import test from 'node:test'
import assert from 'node:assert/strict'
import { fixAlphaTabMidiInfoGetter } from '../../resources/js/metronome/alphatab/fix-midi-info-getter.js'

test('patches alphaTab 1.8.4 recursive MIDI getter and returns its cached value', () => {
    class FakeAlphaSynthWebWorkerApi {
        constructor() { this._loadedMidiInfo = { endTime: 123 } }
        get loadedMidiInfo() { return this.loadedMidiInfo }
    }
    const alphaTab = { synth: { AlphaSynthWebWorkerApi: FakeAlphaSynthWebWorkerApi } }
    assert.equal(fixAlphaTabMidiInfoGetter(alphaTab), true)
    assert.deepEqual(new FakeAlphaSynthWebWorkerApi().loadedMidiInfo, { endTime: 123 })
    assert.equal(fixAlphaTabMidiInfoGetter(alphaTab), false)
})

test('does not override corrected upstream getter', () => {
    class FixedApi {
        get loadedMidiInfo() { return this._loadedMidiInfo }
    }
    const getter = Object.getOwnPropertyDescriptor(FixedApi.prototype, 'loadedMidiInfo').get
    assert.equal(fixAlphaTabMidiInfoGetter({ synth: { AlphaSynthWebWorkerApi: FixedApi } }), false)
    assert.equal(Object.getOwnPropertyDescriptor(FixedApi.prototype, 'loadedMidiInfo').get, getter)
})

test('gracefully skips incompatible exports', () => {
    assert.equal(fixAlphaTabMidiInfoGetter({}), false)
    assert.equal(fixAlphaTabMidiInfoGetter(null), false)
})
