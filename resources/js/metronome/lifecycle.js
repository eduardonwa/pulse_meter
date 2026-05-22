import { isTypingInField } from '../helpers'

export function lifecycle() {
    return {
        init() {
            this.loadFromLocalStorage()

            this.$watch('steps', () => {
                this.saveToLocalStorage()
            })

            this.loadRecentSessions()

            this.loadClickSounds?.()

            this.$nextTick(() => {
                // Cada numberPicker puede centrarse solo.
            })
        },

        handleKeydown(event) {
            if (isTypingInField(event)) {
                return
            }

            if (event.code !== 'Space') {
                return
            }

            if (!this.isWaitingForNextExercise) {
                return
            }

            event.preventDefault()

            this.continueToNextExercise()
        },

        get currentStep() {
            return this.steps[this.currentIndex]
        },

        get currentDawProfile() {
            return this.dawProfiles[this.activeDawProfileKey]
        },
    }
}