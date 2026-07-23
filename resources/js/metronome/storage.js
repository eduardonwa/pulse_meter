import { defaultSteps, defaultMetronome } from './state'

function normalizeMode(mode) {
    return mode === 'manual'
        ? 'classic'
        : mode
}

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

                const normalizedSteps = Array.isArray(parsedSteps)
                    ? parsedSteps.map(step => ({
                        ...step,
                        mode: normalizeMode(step.mode),
                    })) : []

                this.steps = normalizedSteps.length
                    ? normalizedSteps
                    : defaultSteps()

                // Guarda nuevamente los datos ya migrados.
                if (
                    Array.isArray(parsedSteps)
                    && parsedSteps.some(step => step.mode === 'manual')
                ) {
                    this.saveToLocalStorage()
                }
            } catch (error) {
                this.steps = defaultSteps()
            }
        },

        requestClearAllAppStorage() {
            this.showResetAppModal = true
        },

        clearAllAppStorage() {
            this.stop?.()
            this.resetAudio?.()

            localStorage.removeItem(this.storageKey)
            localStorage.removeItem(this.recentSessionsStorageKey)

            this.steps = defaultSteps()
            this.metronome = defaultMetronome()

            this.recentSessions = {
                classic: [],
                timer: [],
            }

            this.currentIndex = 0
            this.activeExerciseIndex = null
            this.remaining = null

            this.saveToLocalStorage?.()
            this.saveRecentSessions?.()

            this.showResetAppModal = false
        }
    }
}