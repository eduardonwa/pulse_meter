export const DEFAULT_SESSION_MODE = 'classic'

export const DEFAULT_SESSION_MODE_STORAGE_KEY =
    'pulse_meter_default_session_mode'

export const SESSION_MODES = [
    'classic',
    'creative',
    'timer',
]

export function getDefaultSessionMode() {
    try {
        const savedMode =
            globalThis.localStorage?.getItem(
                DEFAULT_SESSION_MODE_STORAGE_KEY
            )

        return SESSION_MODES.includes(savedMode)
            ? savedMode
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