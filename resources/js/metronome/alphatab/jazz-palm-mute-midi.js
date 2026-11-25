// Route Jazz palm-muted attacks to the dedicated muted preset on a separate
// MIDI channel. Using a second channel avoids cutting off sustained clean notes.
export function routeJazzPalmMuteMidi(file, score, midi) {
    const beats = score?.tracks?.[0]?.staves?.[0]?.bars?.flatMap(bar =>
        bar.voices?.flatMap(voice => voice.beats ?? []) ?? []) ?? []
    const mutedTicks = new Set(beats
        .filter(beat => beat.notes?.some(note => note.isPalmMute))
        .map(beat => beat.absolutePlaybackStart + (file.tickShift ?? 0)))
    if (!mutedTicks.size) return 0

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
    let sourceChannel = null
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
                sourceChannel ??= event.channel
                event.channel = mutedChannel
                switched++
            }
        } else {
            const wasMuted = active.get(key)?.shift()
            if (wasMuted) event.channel = mutedChannel
        }
    }
    if (switched) {
        // Insert before tick-zero note-ons; this only affects this MIDI file.
        events.unshift(new midi.ProgramChangeEvent(0, 0, mutedChannel, 43))
    }
    return switched
}
