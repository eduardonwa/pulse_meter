export function lifecycle() {
    return {
        init() {
            this.loadFromLocalStorage()

            this.$watch('steps', () => {
                this.saveToLocalStorage()
            })

            this.loadClickSounds?.()

            this.$nextTick(() => {
                // Aquí ya no tienes que forzar todos los pickers globales.
                // Cada numberPicker puede centrarse solo.
            })
        },

        get currentStep() {
            return this.steps[this.currentIndex]
        },

        get currentDawProfile() {
            return this.dawProfiles[this.activeDawProfileKey]
        },
    }
}