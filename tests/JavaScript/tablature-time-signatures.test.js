import test from 'node:test'
import assert from 'node:assert/strict'
import {
    createTabDraft,
    copyTabFragment,
    pasteTabFragment,
    layoutTabMeasures,
    serializeTabDraft,
    parseTabDraft,
    playbackAlphaTex,
    tabLegatoDirection,
    toggleTabLegato,
    normalizeTabLegato,
    toggleTabPalmMute,
    tabTieSource,
    toggleTabTie,
    normalizeTabTies,
} from '../../resources/js/metronome/alphatab/tab-editor.js'

const sevenEighthNotes = {
    ...createTabDraft(),
    beats: Array.from({ length: 7 }, (_, fret) => ({
        fret,
        string: 1,
        duration: 8,
    })),
}

test('7/8 holds seven eighth notes per bar', () => {
    const [measure] = layoutTabMeasures(sevenEighthNotes, 7, 8)
    assert.equal(measure.capacity, 14)
    assert.equal(measure.used, 14)
    assert.equal(measure.complete, true)
})

test('7/8 tablature round-trips through alphaTex and playback', () => {
    const alphaTex = serializeTabDraft(sevenEighthNotes, 7, 120, 8)
    assert.ok(alphaTex.includes(String.raw`\ts 7 8`))
    const parsed = parseTabDraft(alphaTex)
    assert.equal(parsed.numerator, 7)
    assert.equal(parsed.denominator, 8)
    assert.equal(parsed.draft.beats.length, 7)
    assert.equal(playbackAlphaTex(alphaTex), alphaTex)
})

test('4/4 remains the default meter', () => {
    const draft = {
        ...createTabDraft(),
        beats: Array.from({ length: 4 }, () => ({ fret: 0, string: 1, duration: 4 })),
    }
    const alphaTex = serializeTabDraft(draft)
    assert.ok(alphaTex.includes(String.raw`\ts 4 4`))
    assert.equal(parseTabDraft(alphaTex).denominator, 4)
})

test('Pulse Editor meter denominators round-trip through alphaTex', () => {
    for (const denominator of [2, 4, 8, 16]) {
        for (const numerator of [1, 3, 7, 16]) {
            const draft = {
                ...createTabDraft(),
                beats: Array.from({ length: numerator }, () => ({
                    fret: 0, string: 1, duration: denominator,
                })),
            }
            const [measure] = layoutTabMeasures(draft, numerator, denominator)
            assert.equal(measure.complete, true, `${numerator}/${denominator} should fit`)
            const alphaTex = serializeTabDraft(draft, numerator, 120, denominator)
            assert.ok(alphaTex.includes(String.raw`\ts ${numerator} ${denominator}`))
            const parsed = parseTabDraft(alphaTex)
            assert.equal(parsed.numerator, numerator)
            assert.equal(parsed.denominator, denominator)
            assert.equal(playbackAlphaTex(alphaTex), alphaTex)
        }
    }
})

test('invalid time signatures are rejected', () => {
    const draft = { ...createTabDraft(), beats: [{ fret: 0, string: 1, duration: 16 }] }
    for (const [numerator, denominator] of [[0, 4], [17, 4], [7, 3]]) {
        assert.throws(() => serializeTabDraft(draft, numerator, 100, denominator), RangeError)
    }
})


test('legato hammer-on and pull-off round-trip in editable AlphaTex', () => {
    const draft = {
        ...createTabDraft(),
        beats: [
            { fret: 5, string: 2, duration: 4, legato: true },
            { fret: 7, string: 2, duration: 4, legato: true },
            { fret: 5, string: 2, duration: 4 },
            { fret: 4, string: 2, duration: 4 },
        ],
    }
    assert.equal(tabLegatoDirection(draft.beats, 0), 'H')
    assert.equal(tabLegatoDirection(draft.beats, 1), 'P')
    const alphaTex = serializeTabDraft(draft)
    assert.ok(alphaTex.includes('5.2{h}'))
    assert.ok(alphaTex.includes('7.2{h}'))
    assert.deepEqual(parseTabDraft(alphaTex).draft.beats, draft.beats)
    assert.equal(playbackAlphaTex(alphaTex), alphaTex)
})

test('legato can be toggled, but cannot connect rests or unrelated strings', () => {
    const pair = [
        { fret: 5, string: 2, duration: 4 },
        { fret: 7, string: 2, duration: 4 },
    ]
    const enabled = toggleTabLegato(pair, 0)
    assert.equal(enabled[0].legato, true)
    assert.equal(toggleTabLegato(enabled, 0)[0].legato, false)
    assert.equal(toggleTabLegato(pair, 0, 'H')[0].legato, true)
    assert.throws(() => toggleTabLegato(pair, 0, 'P'), RangeError)
    const descending = [{ fret: 7, string: 2, duration: 4 }, { fret: 5, string: 2, duration: 4 }]
    assert.equal(toggleTabLegato(descending, 0, 'P')[0].legato, true)
    assert.throws(() => toggleTabLegato(descending, 0, 'H'), RangeError)
    assert.throws(() => toggleTabLegato(pair, 1), RangeError)
    assert.throws(() => toggleTabLegato([pair[0], { ...pair[1], string: 1 }], 0), RangeError)
    assert.throws(() => toggleTabLegato([pair[0], { rest: true, duration: 4 }], 0), RangeError)
})

test('invalid legato destinations are rejected on save', () => {
    const draft = {
        ...createTabDraft(),
        beats: [{ fret: 5, string: 2, duration: 4, legato: true }, { fret: 5, string: 2, duration: 4 }],
    }
    assert.throws(() => serializeTabDraft(draft), RangeError)
})


test('editing a legato destination removes orphaned effects without touching valid links', () => {
    const beats = [
        { fret: 5, string: 1, duration: 4, legato: true },
        { fret: 7, string: 1, duration: 4, legato: true },
        { fret: 9, string: 2, duration: 4 },
    ]
    const cleaned = normalizeTabLegato(beats)
    assert.equal(cleaned[0].legato, true)
    assert.equal(cleaned[1].legato, undefined)
    assert.equal(beats[1].legato, true)
})

test('palm mute round-trips notes and chords through AlphaTex', () => {
    const draft = {
        ...createTabDraft(),
        beats: [
            { fret: 5, string: 6, duration: 4, palmMute: true },
            { notes: [{ fret: 5, string: 6 }, { fret: 7, string: 5 }], duration: 4, palmMute: true },
            { fret: 7, string: 6, duration: 4 },
            { rest: true, duration: 4 },
        ],
    }
    const alphaTex = serializeTabDraft(draft)
    assert.ok(alphaTex.includes('5.6{pm}'))
    assert.ok(alphaTex.includes('(5.6{pm} 7.5{pm})'))
    assert.deepEqual(parseTabDraft(alphaTex).draft.beats, draft.beats)
    assert.equal(playbackAlphaTex(alphaTex), alphaTex)
})

test('palm mute can coexist with hammer-on and pull-off', () => {
    const draft = {
        ...createTabDraft(),
        beats: [
            { fret: 5, string: 2, duration: 4, legato: true, palmMute: true },
            { fret: 7, string: 2, duration: 4, legato: true, palmMute: true },
            { fret: 5, string: 2, duration: 4 },
            { fret: 5, string: 3, duration: 4 },
        ],
    }
    const alphaTex = serializeTabDraft(draft)
    assert.ok(alphaTex.includes('5.2{h pm}'))
    assert.deepEqual(parseTabDraft(alphaTex).draft.beats, draft.beats)
})

test('palm mute acts on selected notes and ignores rests', () => {
    const beats = [
        { fret: 5, string: 6, duration: 8 },
        { rest: true, duration: 8 },
        { fret: 7, string: 6, duration: 8 },
    ]
    const on = toggleTabPalmMute(beats, [0, 1, 2])
    assert.equal(on[0].palmMute, true)
    assert.equal(on[1].palmMute, undefined)
    assert.equal(on[2].palmMute, true)
    const off = toggleTabPalmMute(on, [0, 2])
    assert.equal(off[0].palmMute, false)
    assert.equal(off[2].palmMute, false)
    assert.deepEqual(toggleTabPalmMute(beats, [1]), beats)
})

test('tied destination round-trips through AlphaTex across a barline', () => {
    const draft = {
        ...createTabDraft(),
        breaks: [2],
        beats: [
            { fret: 5, string: 6, duration: 2 },
            { fret: 4, string: 6, duration: 2 },
            { fret: 4, string: 6, duration: 8, tie: true },
            { rest: true, duration: 8 },
        ],
    }
    assert.equal(tabTieSource(draft.beats, 2), 1)
    const alphaTex = serializeTabDraft(draft)
    assert.ok(alphaTex.includes('4.6{t}'))
    assert.deepEqual(parseTabDraft(alphaTex).draft.beats, draft.beats)
    assert.equal(playbackAlphaTex(alphaTex), alphaTex)
})

test('tie toggles on destination note and rejects different pitches and rests', () => {
    const beats = [{ fret: 4, string: 6, duration: 8 }, { fret: 4, string: 6, duration: 16 }]
    assert.equal(toggleTabTie(beats, 1)[1].tie, true)
    assert.equal(toggleTabTie(toggleTabTie(beats, 1), 1)[1].tie, false)
    assert.throws(() => toggleTabTie(beats, 0), RangeError)
    assert.throws(() => toggleTabTie([beats[0], { ...beats[1], fret: 5 }], 1), RangeError)
    assert.throws(() => toggleTabTie([beats[0], { rest: true, duration: 16 }], 1), RangeError)
    assert.equal(normalizeTabTies([beats[0], { ...beats[1], fret: 5, tie: true }])[1].tie, undefined)
})

test('tie and palm mute round-trip on the same destination note', () => {
    const draft = {
        ...createTabDraft(),
        beats: [
            { fret: 4, string: 6, duration: 4 },
            { fret: 4, string: 6, duration: 4, palmMute: true, tie: true },
        ],
    }
    const alphaTex = serializeTabDraft(draft)
    assert.ok(alphaTex.includes('4.6{pm t}'))
    assert.deepEqual(parseTabDraft(alphaTex).draft.beats, draft.beats)
})

test('clipboard preserves chords, rhythms, techniques and measure breaks', () => {
    const draft = { ...createTabDraft(), breaks: [2], beats: [
        { fret: 5, string: 6, duration: 2, palmMute: true },
        { fret: 5, string: 6, duration: 2, tie: true },
        { notes: [{ fret: 7, string: 5 }, { fret: 5, string: 6 }], duration: 8, dotted: true },
        { rest: true, duration: 16 },
    ] };
    const fragment = copyTabFragment(draft, [0, 1, 2, 3]);
    const destination = { ...createTabDraft(), beats: [{ rest: true, duration: 1 }] };
    const result = pasteTabFragment(destination, 0, fragment);
    assert.deepEqual(result.draft.beats, draft.beats);
    assert.deepEqual(result.draft.breaks, [2]);
    assert.equal(result.count, 4);
    assert.deepEqual(parseTabDraft(serializeTabDraft(result.draft)).draft.beats, draft.beats);
});

test('partial clipboard inserts without overwriting and splits overflowing measures', () => {
    const draft = { ...createTabDraft(), beats: [
        { fret: 3, string: 6, duration: 2 },
        { fret: 5, string: 6, duration: 2 },
    ] };
    const fragment = copyTabFragment(draft, [0]);
    const result = pasteTabFragment(draft, 1, fragment);
    assert.deepEqual(result.draft.beats.map(beat => beat.fret), [3, 3, 5]);
    assert.deepEqual(result.draft.breaks, [2]);
    assert.equal(result.start, 1);
});

test('clipboard does not retain ties or legato to unselected notes', () => {
    const draft = { ...createTabDraft(), beats: [
        { fret: 3, string: 6, duration: 8, legato: true },
        { fret: 5, string: 6, duration: 8 },
        { fret: 5, string: 6, duration: 8, tie: true },
    ] };
    const fragment = copyTabFragment(draft, [0, 2]);
    assert.equal(fragment[0].beats[0].legato, undefined);
    assert.equal(fragment[0].beats[1].tie, undefined);
});
