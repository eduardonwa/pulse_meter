import { readFileSync, writeFileSync } from 'node:fs'

const original = 'this.channelNoteOn(this._metronomeChannel, SynthConstants.MetronomeKey, 95 / 127);'
const accented = 'this.channelNoteOn(this._metronomeChannel, SynthConstants.MetronomeKey, (m.event?.metronomeNumerator === 0 ? 127 : 70) / 127);'

export function patchAlphaTabMetronome(source) {
    if (source.split(original).length !== 2 ||
        !/const m = this\._midiEventQueue\.dequeue\(\);\s*if \(m\.isMetronome/.test(source)) {
        throw new Error('AlphaTab metronome implementation changed; review the accent patch.')
    }
    const replacements = [
        ['let metronomeTick = midiFile.tickShift;', 'let metronomeTick = midiFile.tickShift;\n        let metronomeBeat = 0;'],
        ['Math.floor(metronomeTick / metronomeLengthInTicks) % metronomeCount', 'metronomeBeat'],
        ['metronomeTick += metronomeLengthInTicks;', 'metronomeBeat = (metronomeBeat + 1) % metronomeCount;\n            metronomeTick += metronomeLengthInTicks;'],
        ['metronomeCount = meta.numerator;', 'if (metronomeCount > 0) {\n                    metronomeTick = absTick;\n                    metronomeTime = absTime;\n                }\n                metronomeBeat = 0;\n                metronomeCount = meta.numerator;'],
    ]
    const start = source.indexOf('createStateFromFile(midiFile) {')
    const end = source.indexOf('fillMidiEventQueue() {', start)
    if (start < 0 || end < 0) throw new Error('AlphaTab metronome sequencer changed.')
    let sequencer = source.slice(start, end)
    for (const [before, after] of replacements) {
        if (sequencer.split(before).length !== 2) {
            throw new Error('AlphaTab metronome sequencer changed: ' + before)
        }
        sequencer = sequencer.replace(before, after)
    }
    return (source.slice(0, start) + sequencer + source.slice(end)).replace(original, accented)
}

export function prepareAlphaTabMetronome() {
    const source = readFileSync(new URL('../public/alphatab/alphaTab.core.mjs', import.meta.url), 'utf8')
    const output = new URL('../public/alphatab/alphaTab.core.accent.mjs', import.meta.url)
    writeFileSync(output, patchAlphaTabMetronome(source))
}
