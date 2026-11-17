export const ALPHATAB_COUNT_IN_KEY = 'pulse_meter_alphatab_count_in'

export function isAlphaTabCountInEnabled(storage = globalThis.localStorage) {
    try {
        return storage?.getItem(ALPHATAB_COUNT_IN_KEY) !== 'false'
    } catch {
        return true
    }
}

export function setAlphaTabCountInEnabled(enabled, storage = globalThis.localStorage) {
    try {
        storage?.setItem(ALPHATAB_COUNT_IN_KEY, String(Boolean(enabled)))
        return true
    } catch {
        return false
    }
}
