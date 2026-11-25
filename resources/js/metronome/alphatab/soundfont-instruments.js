// Dorelog instrument IDs 28–30 select a dedicated single-preset guitar SoundFont.
// They are kept as numeric IDs in stored AlphaTex so existing guitar drafts
// (24–27) remain backward-compatible.
export const CUSTOM_GUITAR_FONTS = {
    28: '/soundfont/acoustic.sf2',
    29: '/soundfont/overdrive-gtr.sf2',
    30: '/soundfont/metal-master.sf2',
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
    if (!Object.hasOwn(CUSTOM_GUITAR_FONTS, instrument)) return alphaTex

    // All custom SF2s expose program 0, which alphaTab classifies as a
    // non-string instrument. Declare the standard six-string tuning.
    return String(alphaTex)
        .replace(/^\\instrument\s+\d+\s*$/m, '\\instrument 0')
        .replace(/(\\staff\s*\{\s*tabs\s*\})/i,
            '$1' + '\n\\tuning (E4 B3 G3 D3 A2 E2)')
}
