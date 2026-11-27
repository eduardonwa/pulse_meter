import { readFileSync, writeFileSync } from 'node:fs'

const original = 'this.channelNoteOn(this._metronomeChannel, SynthConstants.MetronomeKey, 95 / 127);'
const accented = 'this.channelNoteOn(this._metronomeChannel, SynthConstants.MetronomeKey, (m.event?.metronomeNumerator === 0 ? 127 : 70) / 127);'

export function patchAlphaTabMetronome(source) {
    if (source.split(original).length !== 2 ||
        !/const m = this\._midiEventQueue\.dequeue\(\);\s*if \(m\.isMetronome/.test(source)) {
        throw new Error('AlphaTab metronome implementation changed; review the accent patch.')
    }
    return source.replace(original, accented)
}

export function prepareAlphaTabMetronome() {
    const source = readFileSync(new URL('../public/alphatab/alphaTab.core.mjs', import.meta.url), 'utf8')
    const output = new URL('../public/alphatab/alphaTab.core.accent.mjs', import.meta.url)
    writeFileSync(output, patchAlphaTabMetronome(source))
}
