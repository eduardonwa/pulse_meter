// alphaTab 1.8.4 has a self-recursive loadedMidiInfo getter. Its
// midiLoaded.on() immediately reads that getter if MIDI is already loaded.
// Register a single listener BEFORE the first score is loaded, then reuse it
// for every subsequent MusicXML replacement.
const pendingMidiLoads = new WeakMap()

export function installMidiReloadListener(api) {
    api.midiLoaded.on(() => {
        const pending = pendingMidiLoads.get(api)
        if (!pending) return
        pendingMidiLoads.delete(api)
        pending.resolve()
    })
    api.error.on(error => {
        const pending = pendingMidiLoads.get(api)
        if (!pending) return
        pendingMidiLoads.delete(api)
        pending.reject(error)
    })
}

export function loadReplacementMusicXml(api, musicXml) {
    return new Promise((resolve, reject) => {
        // A stale waiter must not be resolved by a later score's MIDI.
        const previous = pendingMidiLoads.get(api)
        if (previous) previous.reject(new Error('MusicXML load superseded'))
        pendingMidiLoads.set(api, { resolve, reject })
        try {
            if (!api.load(new TextEncoder().encode(musicXml))) {
                throw new Error('alphaTab did not accept this MusicXML file')
            }
        } catch (error) {
            pendingMidiLoads.delete(api)
            reject(error)
        }
    })
}
