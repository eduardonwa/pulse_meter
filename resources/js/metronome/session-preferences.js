export const DEFAULT_SESSION_MODE = 'classic'

export const DEFAULT_SESSION_MODE_STORAGE_KEY =
    'pulse_meter_default_session_mode'

export const SESSION_MODES = [
    'classic',
    'creative',
    'drill',
]

export function getDefaultSessionMode() {
    try {
        const savedMode =
            globalThis.localStorage?.getItem(
                DEFAULT_SESSION_MODE_STORAGE_KEY
            )

        const normalizedMode = savedMode === 'timer' ? 'drill' : savedMode

        if (normalizedMode !== savedMode && normalizedMode === 'drill') {
            globalThis.localStorage?.setItem(DEFAULT_SESSION_MODE_STORAGE_KEY, normalizedMode)
        }

        return SESSION_MODES.includes(normalizedMode)
            ? normalizedMode
            : DEFAULT_SESSION_MODE
    } catch {
        return DEFAULT_SESSION_MODE
    }
}

export function sessionPreferences() {
    return {
        changeDefaultSessionMode(mode) {
            if (!SESSION_MODES.includes(mode)) {
                return false
            }

            try {
                globalThis.localStorage?.setItem(
                    DEFAULT_SESSION_MODE_STORAGE_KEY,
                    mode
                )
            } catch {
                return false
            }

            this.defaultSessionMode = mode

            return true
        },
    }
}