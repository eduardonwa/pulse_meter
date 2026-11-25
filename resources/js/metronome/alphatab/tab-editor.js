// A compact guitar tab model; advanced techniques and multiple voices remain unsupported.
export const TAB_STRINGS = ['e', 'B', 'G', 'D', 'A', 'E'];
export const TAB_DURATIONS = [1, 2, 4, 8, 16];
export const TAB_INSTRUMENTS = [24, 25, 26, 27, 28, 29, 30, 31];

export function tabBeatUnits(beat) {
    const duration = Number(beat.duration);
    if (!TAB_DURATIONS.includes(duration)) throw new RangeError('Invalid duration');
    return (16 / duration) * (beat.dotted ? 1.5 : 1) * (beat.tuplet === 3 ? 2 / 3 : 1);
}

function tabRhythmProperties(beat) {
    const properties = [];
    if (beat.dotted) properties.push('d');
    if (beat.tuplet === 3) properties.push('tu 3');
    if (beat.pickStroke) {
        if (beat.rest || !['sd', 'su'].includes(beat.pickStroke)) throw new RangeError('Invalid pick stroke');
        properties.push(beat.pickStroke);
    }
    return properties.length ? ` { ${properties.join(' ')} }` : '';
}

export function changeSelectedTabRhythm(beats, indices, changes) {
    const selected = new Set(indices);
    return beats.map((beat, index) => selected.has(index) ? { ...beat, ...changes } : beat);
}
export function createTabDraft() {
    return { beats: [], duration: 8, dotted: false, tuplet: null, string: 1, fret: 0, instrument: 25, breaks: [], tempos: {}, signatures: {} };
}
// Measure changes are sparse, keyed by zero-based measure index.
function tabMeasureValueAt(changes, measureIndex, initialValue) {
    let result = initialValue;
    for (const [index, value] of Object.entries(changes ?? {}).sort(([a], [b]) => Number(a) - Number(b))) {
        if (Number(index) > measureIndex) break;
        result = value;
    }
    return result;
}

export function tabTempoAt(draft, measureIndex, initialBpm) {
    return tabMeasureValueAt(draft.tempos, measureIndex, Number(initialBpm));
}

function validateMeasureChangeIndex(draft, measureIndex) {
    if (!Number.isInteger(measureIndex) || measureIndex < 1 ||
        measureIndex >= layoutTabMeasures(draft).length) throw new RangeError('Choose a measure after the first measure');
}

export function setTabMeasureTempo(draft, measureIndex, bpm) {
    validateMeasureChangeIndex(draft, measureIndex);
    const tempos = { ...draft.tempos };
    if (bpm === null) delete tempos[measureIndex];
    else {
        const value = Number(bpm);
        if (!Number.isInteger(value) || value < 30 || value > 400) throw new RangeError('Choose a BPM between 30 and 400');
        tempos[measureIndex] = value;
    }
    return { ...draft, tempos };
}

export function shiftTabMeasureChanges(changes, index, removed, inserted = 0) {
    return Object.fromEntries(Object.entries(changes ?? {}).flatMap(([key, value]) => {
        const measure = Number(key);
        if (measure >= index && measure < index + removed) return [];
        const next = measure >= index + removed ? measure + inserted - removed : measure;
        return next > 0 ? [[next, value]] : [];
    }));
}

export const shiftTabMeasureTempos = shiftTabMeasureChanges;

function validateSignature(numerator, denominator) {
    const meter = Number(numerator), divisor = Number(denominator);
    if (!Number.isInteger(meter) || meter < 1 || meter > 16) throw new RangeError('Invalid time signature');
    if (![2, 4, 8, 16].includes(divisor)) throw new RangeError('Invalid time signature denominator');
    return { numerator: meter, denominator: divisor };
}

export function tabSignatureAt(draft, measureIndex, numerator = 4, denominator = 4) {
    const signature = tabMeasureValueAt(draft.signatures, measureIndex, validateSignature(numerator, denominator));
    return validateSignature(signature.numerator, signature.denominator);
}

export function setTabMeasureSignature(draft, measureIndex, signature) {
    validateMeasureChangeIndex(draft, measureIndex);
    const signatures = { ...draft.signatures };
    if (signature === null) delete signatures[measureIndex];
    else signatures[measureIndex] = validateSignature(signature.numerator, signature.denominator);
    return { ...draft, signatures };
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
        beats: [...draft.beats, { fret: number, string: line, duration, ...(draft.dotted ? { dotted: true } : {}), ...(draft.tuplet === 3 ? { tuplet: 3 } : {}) }],
    };
}
// A single whole rest represents a full measure in AlphaTab, in any meter.
export function isTabFullMeasureRest(notes) {
    return notes.length === 1 && notes[0].rest && notes[0].duration === 1 &&
        !notes[0].dotted && !notes[0].tuplet;
}

export function layoutTabMeasures(draft, numerator = 4, denominator = 4) {
    const measures = [];
    const boundaries = new Set(draft.breaks ?? []);
    let current = [];
    let units = 0;
    const finish = endIndex => {
        const { numerator: n, denominator: d } = tabSignatureAt(draft, measures.length, numerator, denominator);
        const capacity = n * 16 / d;
        if (isTabFullMeasureRest(current)) units = capacity;
        measures.push({ notes: current, endIndex, complete: Math.abs(units - capacity) < 1e-7, used: units, capacity, overflowBeforeIndex: null });
    };
    validateSignature(numerator, denominator);
    for (const [index, beat] of draft.beats.entries()) {
        if (boundaries.has(index) && current.length) {
            finish(index - 1);
            current = [];
            units = 0;
        }
        current.push({ ...beat, index });
        units += tabBeatUnits(beat);
    }
    if (current.length) finish(draft.beats.length - 1);
    return measures;
}

// The clipboard contains only musical data; no renderer or selection state.
export function copyTabFragment(draft, indices, numerator = 4, denominator = 4) {
    const selected = new Set(indices);
    return layoutTabMeasures(draft, numerator, denominator).map((measure, measureIndex) => {
        const beats = measure.notes.filter(note => selected.has(note.index)).map(({ index, ...note }) => {
            const beat = JSON.parse(JSON.stringify(note));
            if (!selected.has(index - 1)) delete beat.tie;
            if (!selected.has(index + 1)) {
                delete beat.legato;
                if (['ss', 'sl'].includes(beat.slide)) delete beat.slide;
            }
            return beat;
        });
        return { beats, fullRest: isTabFullMeasureRest(measure.notes),
            wholeMeasure: measure.complete && beats.length === measure.notes.length,
            ...(measure.complete && beats.length === measure.notes.length && draft.tempos?.[measureIndex] !== undefined ? { tempo: draft.tempos[measureIndex] } : {}),
            ...(measure.complete && beats.length === measure.notes.length ? { signature: tabSignatureAt(draft, measureIndex, numerator, denominator) } : {}) };
    }).filter(group => group.beats.length);
}

// Insert rather than overwrite existing notes. A selected empty measure is
// replaced, and original barlines outside the insertion remain unchanged.
export function pasteTabFragment(draft, index, fragment, numerator = 4, denominator = 4, insertion = false) {
    if (!fragment.length) return null;
    const measures = layoutTabMeasures(draft, numerator, denominator);
    const groups = measures.map(measure => measure.notes.map(({ index: _, ...beat }) => beat));
    const previous = insertion && index > 0
        ? measures.findIndex(measure => measure.endIndex === index - 1) : -1;
    const current = measures.findIndex(measure => measure.notes.some(note => note.index === index));
    const firstGroup = fragment[0];
    const usePrevious = previous >= 0 && !firstGroup.wholeMeasure &&
        !isTabFullMeasureRest(groups[previous]);
    const target = usePrevious ? previous : current >= 0 ? current : groups.length;
    const original = groups[target] ?? [];
    const offset = usePrevious ? original.length : current >= 0 ? index - measures[current].notes[0].index : 0;
    const replaceRest = !insertion && isTabFullMeasureRest(original);
    const prefix = replaceRest ? [] : original.slice(0, offset);
    const suffix = replaceRest ? [] : original.slice(offset);
    const packed = [];
    const packedSignatures = [];
    const destinationSignature = tabSignatureAt(draft, target, numerator, denominator);
    let signature = destinationSignature;
    let capacity = signature.numerator * 16 / signature.denominator;
    let bar = [], used = 0;
    const setSignature = next => {
        signature = next;
        capacity = signature.numerator * 16 / signature.denominator;
    };
    const flush = () => {
        if (bar.length) {
            packed.push(bar);
            packedSignatures.push(signature);
        }
        bar = [];
        used = 0;
    };
    const append = (beat, fullRest = false) => {
        const size = fullRest ? capacity : tabBeatUnits(beat);
        if (fullRest) flush();
        bar.push(beat);
        used += size;
        if (fullRest) flush();
    };
    prefix.forEach(beat => append(beat));
    const start = groups.slice(0, target).reduce((total, group) => total + group.length, 0) + prefix.length;
    const copied = JSON.parse(JSON.stringify(fragment));
    const pastedTempos = {};
    let count = 0;
    copied.forEach((group, i) => {
        if (i > 0 || group.wholeMeasure) flush();
        if (group.wholeMeasure) setSignature(group.signature ?? destinationSignature);
        if (group.wholeMeasure && group.tempo !== undefined) pastedTempos[target + packed.length] = group.tempo;
        group.beats.forEach(beat => append(beat, group.fullRest));
        count += group.beats.length;
        if (group.wholeMeasure) {
            flush();
            setSignature(destinationSignature);
        }
    });
    suffix.forEach(beat => append(beat, isTabFullMeasureRest(original)));
    flush();
    const tempos = shiftTabMeasureChanges(draft.tempos, target, original.length ? 1 : 0, packed.length);
    // Preserve the destination's instruction at its original surviving content.
    if (original.length && draft.tempos?.[target] !== undefined) {
        const destination = prefix.length || replaceRest ? target : target + Math.max(0, packed.length - 1);
        if (destination > 0) tempos[destination] = draft.tempos[target];
    }
    // Rebuild only the meter instructions affected by the insertion. Existing
    // measures keep their effective signature, including inherited signatures.
    const signatures = shiftTabMeasureChanges(draft.signatures, target, original.length ? 1 : 0, packed.length);
    const sameSignature = (a, b) => a.numerator === b.numerator && a.denominator === b.denominator;
    let preceding = tabSignatureAt(draft, target - 1, numerator, denominator);
    const initialSignature = target === 0 ? packedSignatures[0] : undefined;
    for (const [i, value] of packedSignatures.entries()) {
        const at = target + i;
        if (at > 0 && (!sameSignature(preceding, value) ||
            (i === 0 && draft.signatures?.[target] && !prefix.length && replaceRest))) signatures[at] = value;
        preceding = value;
    }
    const following = target + (original.length ? 1 : 0);
    if (following < groups.length) {
        const value = tabSignatureAt(draft, following, numerator, denominator);
        if (!sameSignature(preceding, value)) signatures[target + packed.length] = value;
    }
    const initialBpm = pastedTempos[0];
    for (const [measure, tempo] of Object.entries(pastedTempos)) {
        if (Number(measure) > 0) tempos[measure] = tempo;
    }
    groups.splice(target, original.length ? 1 : 0, ...packed);
    const beats = [], breaks = [];
    for (const group of groups) {
        if (beats.length) breaks.push(beats.length);
        beats.push(...group);
    }
    // Inserting changes adjacency at both ends. Do not create unintended
    // links from existing notes into the pasted material.
    if (start > 0 && (beats[start - 1].legato || ['ss', 'sl'].includes(beats[start - 1].slide))) {
        beats[start - 1] = { ...beats[start - 1] };
        delete beats[start - 1].legato;
        if (['ss', 'sl'].includes(beats[start - 1].slide)) delete beats[start - 1].slide;
    }
    if (beats[start + count]?.tie) {
        beats[start + count] = { ...beats[start + count] };
        delete beats[start + count].tie;
    }
    return { draft: { ...draft, beats: normalizeTabTies(normalizeTabLegato(beats)), breaks, tempos, signatures }, start, count, ...(initialBpm !== undefined ? { initialBpm } : {}), ...(initialSignature ? { initialSignature } : {}) };
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
export function breaksForInsertedTabNote(draft, index, duration, numerator = 4, denominator = 4) {
    const beats = draft.beats;
    const boundaries = draft.breaks ?? [];
    const previousBoundary = Math.max(0, ...boundaries.filter(value => value < index));
    const previousBeats = beats.slice(previousBoundary, index);
    const measureIndex = boundaries.filter(value => value < index).length;
    const signature = tabSignatureAt(draft, measureIndex, numerator, denominator);
    const capacity = signature.numerator * 16 / signature.denominator;
    const used = isTabFullMeasureRest(previousBeats) ? capacity :
        previousBeats.reduce((total, beat) => total + tabBeatUnits(beat), 0);
    const size = tabBeatUnits({ duration, dotted: draft.dotted, tuplet: draft.tuplet });
    const nextLength = beats.length + 1;
    const shifted = updateTabBreaks(boundaries, index, 'insert', nextLength);
    if (index === beats.length && used > 0 && used + size > capacity + 1e-7) {
        return [...new Set([...shifted, index])].sort((a, b) => a - b);
    }
    if (boundaries.includes(index) && used + size <= capacity + 1e-7) {
        // The inserted beat belongs to the previous measure, not the next.
        return [...new Set(shifted.map(value => value === index ? index + 1 : value))]
            .sort((a, b) => a - b);
    }
    return shifted;
}

// A legato link belongs to the starting note; {h} encodes both H and P.
export function tabLegatoDirection(beats, index) {
    const current = tabBeatNotes(beats[index]);
    const next = tabBeatNotes(beats[index + 1]);
    if (current.length !== 1 || next.length !== 1) return null;
    if (current[0].string !== next[0].string || current[0].fret === next[0].fret) return null;
    return next[0].fret > current[0].fret ? 'H' : 'P';
}

// Editing a fret, string, or beat can invalidate an existing link.
// Remove only orphaned links while preserving all valid legato effects.
export const TAB_SLIDES = ['ss', 'sl', 'sib', 'sia', 'sou', 'sod'];

function validTabSlide(beats, index, slide) {
    return TAB_SLIDES.includes(slide) && tabBeatNotes(beats[index]).length === 1 &&
        (!['ss', 'sl'].includes(slide) || Boolean(tabLegatoDirection(beats, index)));
}

export function toggleTabSlide(beats, index, slide) {
    if (!validTabSlide(beats, index, slide)) {
        throw new RangeError('Slide requires a single note; connected slides need a different fret on the same string next.');
    }
    return beats.map((beat, i) => {
        if (i !== index) return beat;
        const { slide: previous, ...rest } = beat;
        return previous === slide ? rest : { ...rest, legato: false, slide };
    });
}

export function normalizeTabLegato(beats) {
    return beats.map((beat, index) => {
        let result = beat;
        if (beat.legato && !tabLegatoDirection(beats, index)) {
            const { legato, ...rest } = result;
            result = rest;
        }
        if (beat.slide && !validTabSlide(beats, index, beat.slide)) {
            const { slide, ...rest } = result;
            result = rest;
        }
        return result;
    });
}

export function toggleTabLegato(beats, index, technique = null) {
    const direction = tabLegatoDirection(beats, index);
    if (!direction) {
        throw new RangeError('Legato needs two consecutive notes on the same string at different frets.');
    }
    if (technique && technique !== direction) {
        throw new RangeError(technique === 'H'
            ? 'Hammer-on requires the following fret to be higher.'
            : 'Pull-off requires the following fret to be lower.');
    }
    return beats.map((beat, i) => i === index
        ? { ...beat, slide: undefined, ...(beat.legato ? { legato: false } : { legato: true }) }
        : beat);
}

// Ties belong to their destination, unlike hammer-on/pull-off links.
// The first version supports a single note of the same fret and string,
// including connections across measure boundaries.
export function tabTieSource(beats, index) {
    if (!Number.isInteger(index) || index <= 0 || index >= beats.length) return null;
    const source = tabBeatNotes(beats[index - 1]);
    const destination = tabBeatNotes(beats[index]);
    return source.length === 1 && destination.length === 1 &&
        source[0].string === destination[0].string && source[0].fret === destination[0].fret
        ? index - 1 : null;
}

export function toggleTabTie(beats, index) {
    if (tabTieSource(beats, index) === null) {
        throw new RangeError('Tie needs the same fret and string as the preceding note.');
    }
    return beats.map((beat, i) => i === index ? { ...beat, tie: !beat.tie } : beat);
}

export function normalizeTabTies(beats) {
    return beats.map((beat, i) => beat.tie && tabTieSource(beats, i) === null
        ? (() => { const { tie, ...rest } = beat; return rest; })()
        : beat);
}

// Legacy beats contain one fret/string; chord beats contain several notes.
export function tabBeatNotes(beat) {
    if (!beat || beat.rest) return [];
    return beat.notes ?? [{ fret: beat.fret, string: beat.string }];
}

// Palm mute applies to the selected rhythmic positions (all notes in a chord).
export function toggleTabPalmMute(beats, indices) {
    const selected = new Set(indices.filter(index => Number.isInteger(index) && index >= 0 && index < beats.length));
    const targets = [...selected].filter(index => !beats[index]?.rest && tabBeatNotes(beats[index]).length);
    if (!targets.length) return beats;
    const enable = targets.some(index => !beats[index].palmMute);
    return beats.map((beat, index) => targets.includes(index)
        ? { ...beat, ...(enable ? { palmMute: true } : { palmMute: false }) }
        : beat);
}

export function toggleTabPickStroke(beats, indices, direction) {
    if (!['sd', 'su'].includes(direction)) throw new RangeError('Invalid pick stroke');
    const targets = new Set(indices.filter(index => tabBeatNotes(beats[index]).length));
    if (!targets.size) return beats;
    const enable = [...targets].some(index => beats[index].pickStroke !== direction);
    return beats.map((beat, index) => {
        if (!targets.has(index)) return beat;
        const { pickStroke, ...rest } = beat;
        return enable ? { ...rest, pickStroke: direction } : rest;
    });
}

export function setTabBeatNote(beat, fret, string) {
    if (!beat || beat.rest || !Number.isInteger(fret) || fret < 0 || fret > 24 ||
        !Number.isInteger(string) || string < 1 || string > 6) throw new RangeError('Invalid note');
    const notes = tabBeatNotes(beat).filter(note => note.string !== string);
    notes.push({ fret, string });
    notes.sort((a, b) => a.string - b.string);
    const { fret: _fret, string: _string, notes: _notes, ...rhythm } = beat;
    return notes.length === 1
        ? { ...rhythm, ...notes[0] }
        : { ...rhythm, notes };
}

export function serializeTabDraft(draft, numerator = 4, bpm = 100, denominator = 4) {
    if (!draft.beats.length) throw new RangeError('Add at least one note');
    const instrument = Number(draft.instrument ?? 25);
    if (!TAB_INSTRUMENTS.includes(instrument)) throw new RangeError('Invalid instrument');
    const measures = layoutTabMeasures(draft, numerator, denominator);
    const body = measures.map((measure, measureIndex) => {
        const tempo = draft.tempos?.[measureIndex];
        if (tempo !== undefined && (!Number.isInteger(tempo) || tempo < 30 || tempo > 400)) throw new RangeError('Invalid measure tempo');
        const signature = draft.signatures?.[measureIndex];
        const meterPrefix = measureIndex > 0 && signature ? `\\ts ${signature.numerator} ${signature.denominator} ` : '';
        const prefix = meterPrefix + (measureIndex > 0 && tempo !== undefined ? `\\tempo ${tempo} ` : '');
        return prefix + measure.notes.map(beat => {
            const notes = tabBeatNotes(beat);
            if (beat.tie && tabTieSource(draft.beats, beat.index) === null) {
                throw new RangeError('Tie needs the same fret and string as the preceding note.');
            }
            if (beat.legato && !tabLegatoDirection(draft.beats, beat.index)) {
                throw new RangeError('Legato needs two consecutive notes on the same string at different frets.');
            }
            if (beat.slide && (!validTabSlide(draft.beats, beat.index, beat.slide) || beat.legato)) {
                throw new RangeError('Invalid slide.');
            }
            const effects = [beat.legato ? 'h' : null, beat.palmMute ? 'pm' : null, beat.tie ? 't' : null, beat.slide].filter(Boolean);
            const content = beat.rest ? 'r' : notes.length === 1
                ? `${notes[0].fret}.${notes[0].string}${effects.length ? `{${effects.join(' ')}}` : ''}`
                : `(${notes.map(note => `${note.fret}.${note.string}${beat.palmMute ? '{pm}' : ''}`).join(' ')})`;
            return `:${beat.duration} ${content}${tabRhythmProperties(beat)}`;
        }).join(' ');
    }).join(' | ');
    return `\\tempo ${Number(bpm)}\n\\instrument ${instrument}\n\\ts ${Number(numerator)} ${Number(denominator)}\n\\staff {tabs}\n.\n${body}`;
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
    const meter = /^\\ts\s+(\d+)\s+(2|4|8|16)$/.exec(content[1]);
    if (!tempo || !meter || content[2] !== '\\staff {tabs}' || content[3] !== '.') {
        throw new Error('This tablature format cannot be edited here yet');
    }
    const numerator = Number(meter[1]);
    const denominator = Number(meter[2]);
    const bpm = Number(tempo[1]);
    const beats = [];
    const breaks = [];
    const tempos = {};
    const signatures = {};
    for (const [measureIndex, rawMeasure] of content[4].split('|').entries()) {
        let measure = rawMeasure.trim();
        const meterChange = /^\\ts\s+(\d+)\s+(\d+)\s+/.exec(measure);
        if (meterChange) {
            if (!measureIndex) throw new Error('Invalid measure time signature');
            signatures[measureIndex] = validateSignature(meterChange[1], meterChange[2]);
            measure = measure.slice(meterChange[0].length);
        }
        const change = /^\\tempo\s+(\d+)\s+/.exec(measure);
        if (change) {
            const value = Number(change[1]);
            if (!measureIndex || value < 30 || value > 400) throw new Error('Invalid measure tempo');
            tempos[measureIndex] = value;
            measure = measure.slice(change[0].length);
        }
        if (beats.length) breaks.push(beats.length);
        const tokens = measure.trim().match(/:\d+\s+(?:\((?:\d+\.\d+(?:\{pm\})?\s*){2,6}\)|\d+\.\d+(?:\{(?:(?:h|pm|t|ss|sl|sib|sia|sou|sod)(?: (?:h|pm|t|ss|sl|sib|sia|sou|sod))*)\})?|r)(?:\s+\{\s*(?:d|tu 3|sd|su)(?:\s+(?:d|tu 3|sd|su))*\s*\})?/g) ?? [];
        if (!tokens.length || tokens.join(' ').replace(/\s+/g, ' ') !== measure.trim().replace(/\s+/g, ' ')) {
            throw new Error('This tablature contains unsupported notation');
        }
        for (const token of tokens) {
            const match = /^:(\d+)\s+(.+?)(?:\s+\{\s*(.*?)\s*\})?$/.exec(token);
            if (!match) throw new Error('Unsupported note');
            const duration = Number(match[1]);
            const body = match[2];
            const effects = match[3]?.trim() ?? '';
            if (!/^(?:d ?)?(?:tu 3 ?)?(?:sd|su)?$/.test(effects)) throw new Error('Unsupported beat effects');
            const rhythm = { ...(effects.split(' ').includes('d') ? { dotted: true } : {}), ...(effects.includes('tu 3') ? { tuplet: 3 } : {}), ...(effects.split(' ').includes('sd') ? { pickStroke: 'sd' } : effects.split(' ').includes('su') ? { pickStroke: 'su' } : {}) };
            if (!TAB_DURATIONS.includes(duration)) throw new Error('Unsupported duration');
            if (body === 'r') {
                if (rhythm.pickStroke) throw new Error('Pick stroke requires a note');
                beats.push({ rest: true, duration, ...rhythm });
                continue;
            }
            const raw = body.startsWith('(') && body.endsWith(')')
                ? body.slice(1, -1).trim().split(/\s+/)
                : [body];
            const notes = raw.map(part => {
                const note = /^(\d+)\.(\d+)(?:\{((?:h|pm|t|ss|sl|sib|sia|sou|sod)(?: (?:h|pm|t|ss|sl|sib|sia|sou|sod))*)\})?$/.exec(part);
                if (!note) throw new Error('Unsupported note');
                const properties = note[3]?.split(' ') ?? [];
                const slide = properties.find(property => TAB_SLIDES.includes(property));
                if (raw.length !== 1 && properties.some(property => property !== 'pm')) throw new Error('Unsupported chord');
                return { fret: Number(note[1]), string: Number(note[2]),
                    ...(properties.includes('h') ? { legato: true } : {}),
                    ...(slide ? { slide } : {}),
                    ...(note[3]?.includes('pm') ? { palmMute: true } : {}),
                    ...(note[3]?.split(' ').includes('t') ? { tie: true } : {}) };
            });
            if (notes.length > 6 || notes.some(note => note.fret > 24 ||
                note.string < 1 || note.string > 6) ||
                new Set(notes.map(note => note.string)).size !== notes.length) {
                throw new Error('Invalid chord');
            }
            if (notes.length > 1 && notes.some(note => Boolean(note.palmMute) !== Boolean(notes[0].palmMute))) {
                throw new Error('All notes in a chord must share palm mute.');
            }
            const palmMute = notes[0].palmMute ? { palmMute: true } : {};
            beats.push(notes.length === 1
                ? { ...notes[0], duration, ...rhythm }
                : { notes: notes.map(({ fret, string }) => ({ fret, string })), duration, ...rhythm, ...palmMute });
        }
    }
    const draft = { ...createTabDraft(), beats, instrument, breaks, tempos, signatures };
    const canonical = serializeTabDraft(draft, numerator, bpm, denominator);
    if ((withInstrument ? canonical : canonical.replace(/^\\instrument 25\n/m, '')) !== lines.join('\n')) {
        throw new Error('This tablature has custom measure boundaries or notation and cannot be edited here yet');
    }
    return { draft, numerator, denominator, bpm };
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
            ? { rest: true, duration: note.duration, ...(note.dotted ? { dotted: true } : {}), ...(note.tuplet === 3 ? { tuplet: 3 } : {}) }
            : note
    );
}


// Backspace removes only the chosen string; the last note becomes a rest.
export function removeTabNoteAtString(beat, string) {
    if (!beat || beat.rest) return beat;
    const notes = tabBeatNotes(beat);
    if (!notes.some(note => note.string === string)) return beat;
    const remaining = notes.filter(note => note.string !== string);
    if (!remaining.length) return { rest: true, duration: beat.duration, ...(beat.dotted ? { dotted: true } : {}), ...(beat.tuplet === 3 ? { tuplet: 3 } : {}) };
    if (remaining.length === 1) return { ...remaining[0], ...(beat.pickStroke ? { pickStroke: beat.pickStroke } : {}), duration: beat.duration, ...(beat.dotted ? { dotted: true } : {}), ...(beat.tuplet === 3 ? { tuplet: 3 } : {}) };
    return { notes: remaining, ...(beat.pickStroke ? { pickStroke: beat.pickStroke } : {}), duration: beat.duration, ...(beat.dotted ? { dotted: true } : {}), ...(beat.tuplet === 3 ? { tuplet: 3 } : {}) };
}

// Derive playable, meter-safe alphaTex without modifying saved tablature.
// Once a beat overflows a bar, skip it and any later beats in that bar.
export function serializeTabPlayback(draft, numerator = 4, bpm = 100, denominator = 4) {
    const measures = layoutTabMeasures(draft, numerator, denominator);
    if (!measures.some(measure => measure.used > measure.capacity)) {
        return serializeTabDraft(draft, numerator, bpm, denominator);
    }
    const beats = [];
    const breaks = [];
    for (const measure of measures) {
        const capacity = measure.capacity;
        if (beats.length) breaks.push(beats.length);
        let used = 0;
        for (const beat of measure.notes) {
            const amount = isTabFullMeasureRest(measure.notes) ? capacity : tabBeatUnits(beat);
            if (used + amount > capacity + 1e-7) break;
            const { index, ...note } = beat;
            beats.push(note);
            used += amount;
        }
        // Fill remaining time with rests so the next bar starts on schedule.
        let remaining = Math.max(0, Math.round((capacity - used) * 1e7) / 1e7);
        for (const duration of [1, 2, 4, 8, 16]) {
            const amount = 16 / duration;
            while (amount <= remaining) {
                beats.push({ rest: true, duration });
                remaining -= amount;
            }
        }
    }
    return serializeTabDraft({ ...draft, beats, breaks }, numerator, bpm, denominator);
}

export function playbackAlphaTex(alphaTex) {
    try {
        const { draft, numerator, denominator, bpm } = parseTabDraft(alphaTex);
        return serializeTabPlayback(draft, numerator, bpm, denominator);
    } catch {
        // Advanced Filament scores are not part of our restricted editor format.
        return alphaTex;
    }
}

// Editing must retain every beat, including beats beyond the meter limit.
// Routine playback keeps its existing meter-safe representation.
export function editorPreviewAlphaTex(alphaTex, editable = false) {
    return editable ? alphaTex : playbackAlphaTex(alphaTex);
}
