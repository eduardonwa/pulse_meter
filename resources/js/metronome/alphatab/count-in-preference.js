export const ALPHATAB_COUNT_IN_KEY = 'pulse_meter_alphatab_count_in'

export function isAlphaTabCountInEnabled(storage = undefined) {
    try {
        return (storage ?? globalThis.localStorage)?.getItem(ALPHATAB_COUNT_IN_KEY) !== 'false'
    } catch {
        return true
    }
}

export function setAlphaTabCountInEnabled(enabled, storage = undefined) {
    try {
        (storage ?? globalThis.localStorage)?.setItem(ALPHATAB_COUNT_IN_KEY, String(Boolean(enabled)))
        return true
    } catch {
        return false
    }
}
