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
export function layoutTabMeasures(draft, numerator = 4) {
    const meter = Number(numerator);
    if (!Number.isInteger(meter) || meter < 2 || meter > 16) {
        throw new RangeError('Invalid time signature');
    }
    const capacity = meter * 4; // sixteenth-note units for a /4 meter
    const measures = [];
    let current = [];
    let units = 0;
    for (const [index, beat] of draft.beats.entries()) {
        const duration = Number(beat.duration);
        if (!TAB_DURATIONS.includes(duration)) throw new RangeError('Invalid duration');
        const amount = 16 / duration;
        // A note that does not fit starts the next measure.
        if (current.length && units + amount > capacity) {
            measures.push({ notes: current, endIndex: index - 1, complete: false, used: units, capacity });
            current = [];
            units = 0;
        }
        current.push({ ...beat, index });
        units += amount;
        if (units === capacity) {
            measures.push({ notes: current, endIndex: index, complete: true, used: units, capacity });
            current = [];
            units = 0;
        }
    }
    if (current.length) {
        measures.push({ notes: current, endIndex: draft.beats.length - 1, complete: false, used: units, capacity });
    }
    return measures;
}

export function serializeTabDraft(draft, numerator = 4, bpm = 100) {
    if (!draft.beats.length) throw new RangeError('Add at least one note');
    const measures = layoutTabMeasures(draft, numerator);
    const body = measures.map(measure => measure.notes.map(note =>
        `:${note.duration} ${note.fret}.${note.string}`
    ).join(' ')).join(' | ');
    return `\\tempo ${Number(bpm)}\n\\ts ${Number(numerator)} 4\n\\staff {tabs}\n.\n${body}`;
}
