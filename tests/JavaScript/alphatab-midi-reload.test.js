import test from 'node:test'
import assert from 'node:assert/strict'
import { installMidiReloadListener, loadReplacementMusicXml } from '../../resources/js/metronome/alphatab/midi-reload.js'

function fakeApi() {
    const midiListeners = []
    const errorListeners = []
    let alreadyLoaded = false
    const api = {
        calls: [],
        midiLoaded: {
            on(callback) {
                // Simulate alphaTab 1.8.4: a late subscription reads its
                // self-recursive loadedMidiInfo getter and throws.
                if (alreadyLoaded) throw new Error('too much recursion')
                midiListeners.push(callback)
            },
        },
        error: { on(callback) { errorListeners.push(callback) } },
        load(bytes) {
            api.calls.push(new TextDecoder().decode(bytes))
            alreadyLoaded = true
            return true
        },
        emitMidi() { midiListeners.forEach(callback => callback()) },
        emitError(error) { errorListeners.forEach(callback => callback(error)) },
    }
    return api
}

test('one early midiLoaded subscription supports repeated MusicXML replacements', async () => {
    const api = fakeApi()
    installMidiReloadListener(api)
    const first = loadReplacementMusicXml(api, '<first/>')
    api.emitMidi()
    await first
    const second = loadReplacementMusicXml(api, '<second/>')
    api.emitMidi()
    await second
    assert.deepEqual(api.calls, ['<first/>', '<second/>'])
})

test('reload rejects a failed import without leaving the next one blocked', async () => {
    const api = fakeApi()
    installMidiReloadListener(api)
    const first = loadReplacementMusicXml(api, '<bad/>')
    const issue = new Error('Invalid MusicXML')
    api.emitError(issue)
    await assert.rejects(first, issue)
    const second = loadReplacementMusicXml(api, '<good/>')
    api.emitMidi()
    await second
})

test('reload rejects when alphaTab refuses the replacement', async () => {
    const api = fakeApi()
    installMidiReloadListener(api)
    api.load = () => false
    await assert.rejects(loadReplacementMusicXml(api, '<bad/>'), /did not accept/)
})

test('superseded loads do not leave the previous promise pending', async () => {
    const api = fakeApi()
    installMidiReloadListener(api)
    const old = loadReplacementMusicXml(api, '<old/>')
    const oldRejection = assert.rejects(old, /superseded/)
    const next = loadReplacementMusicXml(api, '<new/>')
    api.emitMidi()
    await Promise.all([oldRejection, next])
})
