// A deliberately small, deterministic score model. More expressive alphaTex
// (techniques, tuplets, voices) stays in Filament, not this editor.
export const TAB_STRINGS = ['e', 'B', 'G', 'D', 'A', 'E'];
export const TAB_DURATIONS = [1, 2, 4, 8, 16];
export function createTabDraft() {
    return { beats: [], duration: 8, string: 1, fret: 0 };
}
export function appendTabNote(draft, fret = draft.fret, string = draft.string) {
    const number = Number(fret);
    const line = Number(string);
    if (!Number.isInteger(number) || number < 0 || number > 24 ||
        !Number.isInteger(line) || line < 1 || line > 6) {
        throw new RangeError('Choose a fret between 0–24 and a string between 1–6.');
    }
    const duration = Number(draft.duration);
    if (!TAB_DURATIONS.includes(duration)) throw new RangeError('Invalid duration');
    return {
        ...draft,
        beats: [...draft.beats, { fret: number, string: line, duration }],
    };
}
export function serializeTabDraft(draft, numerator = 4, bpm = 100) {
    const meter = Number(numerator);
    if (!Number.isInteger(meter) || meter < 2 || meter > 16) {
        throw new RangeError('Invalid time signature');
    }
    if (!draft.beats.length) throw new RangeError('Add at least one note');
    const measures = [];
    let current = [];
    let units = 0;
    const capacity = meter * 4; // sixteenths per 4-based measure
    for (const beat of draft.beats) {
        const amount = 16 / beat.duration;
        if (units + amount > capacity && current.length) {
            measures.push(current.join(' '));
            current = [];
            units = 0;
        }
        current.push(`:${beat.duration} ${beat.fret}.${beat.string}`);
        units += amount;
        if (units === capacity) {
            measures.push(current.join(' '));
            current = [];
            units = 0;
        }
    }
    if (current.length) measures.push(current.join(' '));
    return `\\tempo ${Number(bpm)}\n\\staff {tabs}\n.\n${measures.join(' | ')}`;
}
