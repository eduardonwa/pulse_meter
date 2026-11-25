import test from 'node:test'
import assert from 'node:assert/strict'
import { routeJazzPalmMuteMidi } from '../../resources/js/metronome/alphatab/jazz-palm-mute-midi.js'

const midi = {
    MidiEventType: { NoteOn: 128, NoteOff: 144 },
    ProgramChangeEvent: class {
        constructor(track, tick, channel, program) {
            Object.assign(this, { track, tick, channel, program, type: 192 })
        }
    },
}

function score() {
    return { tracks: [{ staves: [{ bars: [{ voices: [{ beats: [
        { absolutePlaybackStart: 0, notes: [{ isPalmMute: false }] },
        { absolutePlaybackStart: 480, notes: [{ isPalmMute: true }] },
        { absolutePlaybackStart: 960, notes: [{ isPalmMute: false }] },
    ] }] }] }] }] }
}

function note(type, tick, key, velocity = 85) {
    return { type, tick, track: 0, channel: 0, noteKey: key, noteVelocity: velocity }
}

test('routes only muted Jazz attacks and corresponding releases to a spare channel', () => {
    const events = [
        note(128, 0, 64), note(144, 479, 64, 0),
        note(128, 480, 64), note(144, 700, 64, 0),
        note(128, 960, 64), note(144, 1200, 64, 0),
    ]
    const file = { events, addEvent(event) { this.events.unshift(event) } }
    assert.equal(routeJazzPalmMuteMidi(file, score(), midi), 1)
    assert.equal(events[0].program, 43)
    assert.equal(events[1].channel, 0)
    assert.equal(events[3].channel, 15)
    assert.equal(events[4].channel, 15)
    assert.equal(events[5].channel, 0)
})

test('leaves scores without palm mute untouched', () => {
    const events = [note(128, 0, 64)]
    assert.equal(routeJazzPalmMuteMidi({ events }, { tracks: [] }, midi), 0)
    assert.equal(events.length, 1)
})
