// Work around alphaTab 1.8.4 (build 34): AlphaSynthWebWorkerApi's
// loadedMidiInfo getter returns this.loadedMidiInfo, recursively.
// AlphaTabApi.midiLoaded replays the latest MIDI information by reading it
// when listeners subscribe, so even the first subscription can fail.
// The upstream getter is meant to expose its cached _loadedMidiInfo value.
export function fixAlphaTabMidiInfoGetter(alphaTab) {
    const prototype = alphaTab?.synth?.AlphaSynthWebWorkerApi?.prototype
    if (!prototype) return false
    const descriptor = Object.getOwnPropertyDescriptor(prototype, 'loadedMidiInfo')
    if (!descriptor?.configurable || typeof descriptor.get !== 'function') return false

    // Do not override future alphaTab releases that fix their own getter.
    if (!/this\.loadedMidiInfo\b/.test(descriptor.get.toString())) return false

    Object.defineProperty(prototype, 'loadedMidiInfo', {
        ...descriptor,
        get() { return this._loadedMidiInfo },
    })
    return true
}
