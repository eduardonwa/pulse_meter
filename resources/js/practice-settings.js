import { ALPHATAB_COUNT_IN_KEY, isAlphaTabCountInEnabled, setAlphaTabCountInEnabled } from './metronome/alphatab/count-in-preference.js'
const AUTO_ADVANCE_KEY = 'pulse_meter_auto_advance'
const CLICK_PROFILE_KEY = 'pulse_meter_daw_profile'
const EXERCISES_KEY = 'pulse_meter_routine'
const RECENT_SESSIONS_KEY = 'pulse_meter_recent_sessions'
const SESSION_MODE_KEY = 'pulse_meter_default_session_mode'

export function registerPracticeSettings(Alpine) {
    Alpine.data('alphaTabCountInControl', () => ({
        enabled: isAlphaTabCountInEnabled(),
        toggle() {
            const next = !this.enabled
            if (!setAlphaTabCountInEnabled(next)) return
            this.enabled = next
            window.dispatchEvent(new CustomEvent('alphatab:count-in-changed', {
                detail: { enabled: next },
            }))
        },
    }))
    Alpine.data('practiceSettings', ({ importPreferenceUrl = null, initialImportPreference = true } = {}) => ({
        autoAdvance: localStorage.getItem(AUTO_ADVANCE_KEY) !== 'false',
        alphaTabCountIn: isAlphaTabCountInEnabled(),
        clickProfile: localStorage.getItem(CLICK_PROFILE_KEY) || 'cubase',
        askBeforeImportingFreeExercises: initialImportPreference,
        importPreferenceBusy: false,
        importPreferenceUrl,
        showResetAppModal: false,
        feedback: '',

        setAlphaTabCountIn(enabled) {
            if (!setAlphaTabCountInEnabled(enabled)) return
            this.alphaTabCountIn = enabled
            window.dispatchEvent(new CustomEvent('alphatab:count-in-changed', {
                detail: { enabled },
            }))
        },

        setAutoAdvance(enabled) {
            this.autoAdvance = enabled
            localStorage.setItem(AUTO_ADVANCE_KEY, String(enabled))
        },

        setClickProfile(value) {
            if (!['ableton', 'cubase'].includes(value)) return
            this.clickProfile = value
            localStorage.setItem(CLICK_PROFILE_KEY, value)
        },

        async setImportPreference(enabled) {
            if (!this.importPreferenceUrl || this.importPreferenceBusy) return
            this.importPreferenceBusy = true
            try {
                const response = await fetch(this.importPreferenceUrl, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ enabled }),
                })
                if (!response.ok) throw new Error('Could not save the preference')
                this.askBeforeImportingFreeExercises = enabled
            } catch (error) {
                this.feedback = 'Could not save the import preference.'
                this.$nextTick(() => {
                    const checkbox = this.$root.querySelector('.free-exercise-import-field input')
                    if (checkbox) checkbox.checked = this.askBeforeImportingFreeExercises
                })
            } finally {
                this.importPreferenceBusy = false
            }
        },

        clearAllAppStorage() {
            // Mirror the previous local reset without touching server-side routines.
            for (const key of [EXERCISES_KEY, RECENT_SESSIONS_KEY, SESSION_MODE_KEY, AUTO_ADVANCE_KEY, ALPHATAB_COUNT_IN_KEY]) {
                localStorage.removeItem(key)
            }
            this.autoAdvance = true
            this.alphaTabCountIn = true
            window.dispatchEvent(new CustomEvent('alphatab:count-in-changed', {
                detail: { enabled: true },
            }))
            this.showResetAppModal = false
            this.feedback = 'Local settings and exercises reset.'
        },
    }))
}
