// Route palm-muted attacks to a dedicated guitar preset on a separate MIDI
// channel, avoiding interruption of sustained unmuted notes.
// Jazz: 43; Overdrive: 50. No changes to Distortion.
export function routeJazzPalmMuteMidi(file, score, midi, mutedProgram = 43) {
    const beats = score?.tracks?.[0]?.staves?.[0]?.bars?.flatMap(bar =>
        bar.voices?.flatMap(voice => voice.beats ?? []) ?? []) ?? []
    const mutedTicks = new Set(beats
        .filter(beat => beat.notes?.some(note => note.isPalmMute))
        .map(beat => beat.absolutePlaybackStart + (file.tickShift ?? 0)))
    if (!mutedTicks.size || ![43, 50].includes(mutedProgram)) return 0

    const events = file.events
    const usedChannels = new Set(events
        .filter(event => event.channel !== undefined)
        .map(event => event.channel))
    const mutedChannel = [15, 14, 13, 12, 11, 10, 8, 7, 6, 5, 4, 3, 2, 1]
        .find(channel => !usedChannels.has(channel))
    if (mutedChannel === undefined) return 0

    // Track every note-on, not just muted ones: note-offs must retain the
    // channel where their matching attack was sent.
    const active = new Map()
    let switched = 0
    for (const event of events) {
        if (event.type !== midi.MidiEventType.NoteOn &&
            event.type !== midi.MidiEventType.NoteOff) continue
        const key = `${event.channel}:${event.noteKey}`
        const isOn = event.type === midi.MidiEventType.NoteOn && event.noteVelocity > 0
        if (isOn) {
            const useMuted = event.track === 0 && mutedTicks.has(event.tick)
            if (!active.has(key)) active.set(key, [])
            active.get(key).push(useMuted)
            if (useMuted) {
                event.channel = mutedChannel
                switched++
            }
        } else {
            const wasMuted = active.get(key)?.shift()
            if (wasMuted) event.channel = mutedChannel
        }
    }
    if (switched) {
        // Insert into the real MidiFile, not a merged events snapshot.
        file.addEvent(new midi.ProgramChangeEvent(0, 0, mutedChannel, mutedProgram))
    }
    return switched
}
