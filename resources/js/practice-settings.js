const AUTO_ADVANCE_KEY = 'pulse_meter_auto_advance'
const CLICK_PROFILE_KEY = 'pulse_meter_daw_profile'
const EXERCISES_KEY = 'pulse_meter_routine'
const RECENT_SESSIONS_KEY = 'pulse_meter_recent_sessions'
const SESSION_MODE_KEY = 'pulse_meter_default_session_mode'

export function registerPracticeSettings(Alpine) {
    Alpine.data('practiceSettings', ({ importPreferenceUrl = null, initialImportPreference = true } = {}) => ({
        autoAdvance: localStorage.getItem(AUTO_ADVANCE_KEY) !== 'false',
        clickProfile: localStorage.getItem(CLICK_PROFILE_KEY) || 'cubase',
        askBeforeImportingFreeExercises: initialImportPreference,
        importPreferenceBusy: false,
        importPreferenceUrl,
        showResetAppModal: false,
        feedback: '',

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
            for (const key of [EXERCISES_KEY, RECENT_SESSIONS_KEY, SESSION_MODE_KEY, AUTO_ADVANCE_KEY]) {
                localStorage.removeItem(key)
            }
            this.autoAdvance = true
            this.showResetAppModal = false
            this.feedback = 'Local settings and exercises reset.'
        },
    }))
}
