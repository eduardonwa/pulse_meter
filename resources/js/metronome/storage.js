import { defaultSteps, defaultMetronome } from './state'

export function storage() {
    return {
        saveToLocalStorage() {
            localStorage.setItem(this.storageKey, JSON.stringify(this.steps))
        },

        loadFromLocalStorage() {
            const saved = localStorage.getItem(this.storageKey)

            if (!saved) {
                this.steps = defaultSteps()
                return
            }

            try {
                const parsedSteps = JSON.parse(saved)

                this.steps = Array.isArray(parsedSteps) && parsedSteps.length
                    ? parsedSteps
                    : defaultSteps()
            } catch (error) {
                this.steps = defaultSteps()
            }
        },

        clearAllAppStorage() {
            const confirmed = window.confirm(
                'Reset app? This will delete your custom exercises and all recent sessions.'
            )

            if (!confirmed) {
                return
            }

            this.stop?.()

            localStorage.removeItem(this.storageKey)
            localStorage.removeItem(this.recentSessionsStorageKey)

            this.steps = defaultSteps()
            this.metronome = defaultMetronome()

            this.recentSessions = {
                manual: [],
                timer: [],
            }

            this.currentIndex = 0
            this.activeExerciseIndex = null
            this.remaining = null

            this.saveToLocalStorage?.()
            this.saveRecentSessions?.()
        },
    }
}