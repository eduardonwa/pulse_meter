// A deliberately small, deterministic score model. More expressive alphaTex
// (techniques, tuplets, voices) stays in Filament, not this editor.
export const TAB_STRINGS = ['e', 'B', 'G', 'D', 'A', 'E'];
export const TAB_DURATIONS = [1, 2, 4, 8, 16];
export const TAB_INSTRUMENTS = [24, 25, 26, 27];
export function createTabDraft() {
    return { beats: [], duration: 8, string: 1, fret: 0, instrument: 25, breaks: [] };
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
    const capacity = meter * 4;
    const measures = [];
    const boundaries = new Set(draft.breaks ?? []);
    let current = [];
    let units = 0;
    for (const [index, beat] of draft.beats.entries()) {
        if (boundaries.has(index) && current.length) {
            measures.push({ notes: current, endIndex: index - 1, complete: units === capacity, used: units, capacity, overflowBeforeIndex: null });
            current = [];
            units = 0;
        }
        const duration = Number(beat.duration);
        if (!TAB_DURATIONS.includes(duration)) throw new RangeError('Invalid duration');
        current.push({ ...beat, index });
        units += 16 / duration;
    }
    if (current.length) {
        measures.push({ notes: current, endIndex: draft.beats.length - 1, complete: units === capacity, used: units, capacity, overflowBeforeIndex: null });
    }
    return measures;
}

export function shiftTabBreaks(breaks, index, delta) {
    return [...new Set(breaks.map(position => position >= index ? position + delta : position)
        .filter(position => position > 0))].sort((a, b) => a - b);
}

// Insert at a barline belongs to the following bar; deletion keeps the next
// surviving note at its existing barline. Never retain a trailing barline.
export function updateTabBreaks(breaks, index, operation, length) {
    return [...new Set(breaks.map(position => {
        if (operation === 'insert') return position > index ? position + 1 : position;
        return position > index ? position - 1 : position;
    }).filter(position => position > 0 && position < length))].sort((a, b) => a - b);
}

// When typing at a measure boundary, fill the preceding bar if its
// remaining space fits the note; otherwise keep the new note in the next bar.
// At the end of the score, automatically start a new measure when necessary.
export function breaksForInsertedTabNote(draft, index, duration, numerator = 4) {
    const beats = draft.beats;
    const boundaries = draft.breaks ?? [];
    const previousBoundary = Math.max(0, ...boundaries.filter(value => value < index));
    const used = beats.slice(previousBoundary, index)
        .reduce((total, beat) => total + 16 / beat.duration, 0);
    const size = 16 / duration;
    const capacity = Number(numerator) * 4;
    const nextLength = beats.length + 1;
    const shifted = updateTabBreaks(boundaries, index, 'insert', nextLength);
    if (index === beats.length && used > 0 && used + size > capacity) {
        return [...new Set([...shifted, index])].sort((a, b) => a - b);
    }
    if (boundaries.includes(index) && used + size <= capacity) {
        // The inserted beat belongs to the previous measure, not the next.
        return [...new Set(shifted.map(value => value === index ? index + 1 : value))]
            .sort((a, b) => a - b);
    }
    return shifted;
}

// Legacy beats contain one fret/string; chord beats contain several notes.
export function tabBeatNotes(beat) {
    if (!beat || beat.rest) return [];
    return beat.notes ?? [{ fret: beat.fret, string: beat.string }];
}

export function setTabBeatNote(beat, fret, string) {
    if (!beat || beat.rest || !Number.isInteger(fret) || fret < 0 || fret > 24 ||
        !Number.isInteger(string) || string < 1 || string > 6) throw new RangeError('Invalid note');
    const notes = tabBeatNotes(beat).filter(note => note.string !== string);
    notes.push({ fret, string });
    notes.sort((a, b) => a.string - b.string);
    return notes.length === 1
        ? { duration: beat.duration, ...notes[0] }
        : { duration: beat.duration, notes };
}

export function serializeTabDraft(draft, numerator = 4, bpm = 100) {
    if (!draft.beats.length) throw new RangeError('Add at least one note');
    const instrument = Number(draft.instrument ?? 25);
    if (!TAB_INSTRUMENTS.includes(instrument)) throw new RangeError('Invalid instrument');
    const measures = layoutTabMeasures(draft, numerator);
    const body = measures.map(measure => measure.notes.map(beat => {
        const notes = tabBeatNotes(beat);
        const content = beat.rest ? 'r' : notes.length === 1
            ? `${notes[0].fret}.${notes[0].string}`
            : `(${notes.map(note => `${note.fret}.${note.string}`).join(' ')})`;
        return `:${beat.duration} ${content}`;
    }).join(' ')).join(' | ');
    return `\\tempo ${Number(bpm)}\n\\instrument ${instrument}\n\\ts ${Number(numerator)} 4\n\\staff {tabs}\n.\n${body}`;
}


// Parse only the subset generated by this editor. Reject template/advanced
// alphaTex instead of silently dropping musical information on save.
export function parseTabDraft(alphaTex) {
    if (typeof alphaTex !== 'string') throw new TypeError('Missing tablature');
    const lines = alphaTex.trim().split(/\r?\n/).map(line => line.trim()).filter(Boolean);
    if (lines.length !== 5 && lines.length !== 6) throw new Error('This tablature format cannot be edited here yet');
    const withInstrument = lines.length === 6;
    const instrumentMatch = withInstrument ? /^\\instrument\s+(\d+)$/.exec(lines[1]) : null;
    const instrument = instrumentMatch ? Number(instrumentMatch[1]) : 25;
    if (withInstrument && (!instrumentMatch || !TAB_INSTRUMENTS.includes(instrument))) throw new Error('Unsupported instrument');
    const content = withInstrument ? [lines[0], ...lines.slice(2)] : lines;
    const tempo = /^\\tempo\s+(\d+)$/.exec(lines[0]);
    const meter = /^\\ts\s+(\d+)\s+4$/.exec(content[1]);
    if (!tempo || !meter || content[2] !== '\\staff {tabs}' || content[3] !== '.') {
        throw new Error('This tablature format cannot be edited here yet');
    }
    const numerator = Number(meter[1]);
    const bpm = Number(tempo[1]);
    const beats = [];
    const breaks = [];
    for (const measure of content[4].split('|')) {
        if (beats.length) breaks.push(beats.length);
        const tokens = measure.trim().match(/:\d+\s+(?:\((?:\d+\.\d+\s*){2,6}\)|\d+\.\d+|r)/g) ?? [];
        if (!tokens.length || tokens.join(' ').replace(/\s+/g, ' ') !== measure.trim().replace(/\s+/g, ' ')) {
            throw new Error('This tablature contains unsupported notation');
        }
        for (const token of tokens) {
            const match = /^:(\d+)\s+(.+)$/.exec(token);
            if (!match) throw new Error('Unsupported note');
            const duration = Number(match[1]);
            const body = match[2];
            if (!TAB_DURATIONS.includes(duration)) throw new Error('Unsupported duration');
            if (body === 'r') {
                beats.push({ rest: true, duration });
                continue;
            }
            const raw = body.startsWith('(') && body.endsWith(')')
                ? body.slice(1, -1).trim().split(/\s+/)
                : [body];
            const notes = raw.map(part => {
                const note = /^(\d+)\.(\d+)$/.exec(part);
                if (!note) throw new Error('Unsupported chord');
                return { fret: Number(note[1]), string: Number(note[2]) };
            });
            if (notes.length > 6 || notes.some(note => note.fret > 24 ||
                note.string < 1 || note.string > 6) ||
                new Set(notes.map(note => note.string)).size !== notes.length) {
                throw new Error('Invalid chord');
            }
            beats.push(notes.length === 1 ? { ...notes[0], duration } : { notes, duration });
        }
    }
    const draft = { ...createTabDraft(), beats, instrument, breaks };
    const canonical = serializeTabDraft(draft, numerator, bpm);
    if ((withInstrument ? canonical : canonical.replace(/^\\instrument 25\n/m, '')) !== lines.join('\n')) {
        throw new Error('This tablature has custom measure boundaries or notation and cannot be edited here yet');
    }
    return { draft, numerator, bpm };
}


export function selectTabNoteIndices(selected, anchor, index, mode = 'single', count = Infinity) {
    if (!Number.isInteger(index) || index < 0 || index >= count) return [...selected];
    if (mode === 'range') {
        const start = Number.isInteger(anchor) && anchor >= 0 && anchor < count ? anchor : index;
        return Array.from({ length: Math.abs(index - start) + 1 }, (_, i) => Math.min(start, index) + i);
    }
    if (mode === 'toggle') {
        return selected.includes(index) ? selected.filter(i => i !== index) : [...selected, index].sort((a, b) => a - b);
    }
    return [index];
}

export function changeSelectedTabDurations(beats, indices, duration) {
    const value = Number(duration);
    if (!TAB_DURATIONS.includes(value)) throw new RangeError('Invalid duration');
    const selected = new Set(indices);
    return beats.map((note, index) => selected.has(index) ? { ...note, duration: value } : note);
}

export function clearTabNotes(beats, indices) {
    const selected = new Set(indices);

    return beats.map((note, index) =>
        selected.has(index)
            ? { rest: true, duration: note.duration }
            : note
    );
}


// Derive playable, meter-safe alphaTex without modifying saved tablature.
// Once a beat overflows a bar, skip it and any later beats in that bar.
export function serializeTabPlayback(draft, numerator = 4, bpm = 100) {
    const measures = layoutTabMeasures(draft, numerator);
    if (!measures.some(measure => measure.used > measure.capacity)) {
        return serializeTabDraft(draft, numerator, bpm);
    }
    const capacity = Number(numerator) * 4;
    const beats = [];
    const breaks = [];
    for (const measure of measures) {
        if (beats.length) breaks.push(beats.length);
        let used = 0;
        for (const beat of measure.notes) {
            const amount = 16 / beat.duration;
            if (used + amount > capacity) break;
            const { index, ...note } = beat;
            beats.push(note);
            used += amount;
        }
        // Fill remaining time with rests so the next bar starts on schedule.
        let remaining = capacity - used;
        for (const duration of [1, 2, 4, 8, 16]) {
            const amount = 16 / duration;
            while (amount <= remaining) {
                beats.push({ rest: true, duration });
                remaining -= amount;
            }
        }
    }
    return serializeTabDraft({ ...draft, beats, breaks }, numerator, bpm);
}

export function playbackAlphaTex(alphaTex) {
    try {
        const { draft, numerator, bpm } = parseTabDraft(alphaTex);
        return serializeTabPlayback(draft, numerator, bpm);
    } catch {
        // Advanced Filament scores are not part of our restricted editor format.
        return alphaTex;
    }
}
