// Persist stable Dorelog instrument IDs; translate only for alphaTab playback.
// 24–27 retain their legacy Sonivox meaning; 28 remains acoustic.
export const CUSTOM_GUITAR_FONTS = {
    28: '/soundfont/acoustic.sf2',
    29: '/soundfont/dorelog-6.sf3',
    30: '/soundfont/dorelog-6.sf3',
    31: '/soundfont/dorelog-6.sf3',
}

export const GUITAR_PLAYBACK_PROGRAMS = {
    28: 0,
    29: 49, // Overdrive GU DynPick
    30: 55, // Dist SG Dynamic
    31: 35, // Jazz Guitar GU
}

export function guitarFontForInstrument(instrument) {
    return CUSTOM_GUITAR_FONTS[Number(instrument)] ?? '/soundfont/sonivox.sf2'
}

export function scoreGuitarInstrument(alphaTex) {
    const match = String(alphaTex ?? '').match(/^\\instrument\s+(\d+)\s*$/m)
    return match ? Number(match[1]) : 25
}

export function soundFontPlaybackTex(alphaTex) {
    const instrument = scoreGuitarInstrument(alphaTex)
    const program = GUITAR_PLAYBACK_PROGRAMS[instrument]
    if (program === undefined) return alphaTex

    // The custom program IDs are playback details, not persisted notation.
    // Declare guitar tuning explicitly even for non-GM program numbers.
    return String(alphaTex)
        .replace(/^\\instrument\s+\d+\s*$/m, '\\instrument ' + program)
        .replace(/(\\staff\s*\{\s*tabs\s*\})/i,
            '$1' + '\n\\tuning (E4 B3 G3 D3 A2 E2)')
}
