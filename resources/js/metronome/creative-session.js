const CREATIVE_MODES = [
    'pulse-editor',
    'randomizer',
]

export function creativeSession() {
    return {
        creativeMode: null,

        selectCreativeMode(mode) {
            if (!CREATIVE_MODES.includes(mode)) {
                return false
            }

            this.creativeMode = mode

            return true
        },
    }
}
