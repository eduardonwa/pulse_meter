import { isTypingInField } from '../helpers'

export function lifecycle() {
    return {
        // INITIALIZATION
        async init() {
            this.loadFromLocalStorage()

            this.$watch('steps', () => {
                this.saveToLocalStorage()
            })

            this.loadRecentSessions()
            this.loadClickSounds?.()

            await this.loadPulsePatterns?.()
            await this.restorePulseSource()
            
            this.setPulseBaseline?.()

            this.$nextTick(() => {
                // Cada numberPicker puede centrarse solo.
            })
        },

        // GLOBAL SHORCUTS
        handleKeydown(event) {
            if (
                event.key === 'Escape'
                && this.toolTether.active
            ) {
                this.cancelToolTether()
                return
            }

            if (isTypingInField(event)) {
                return
            }

            if (event.code !== 'Space') {
                return
            }

            event.preventDefault()

            if (event.repeat) {
                return
            }

            if (this.isWaitingForNextExercise) {
                this.continueToNextExercise()
                return
            }

            this.toggle()
        },

        // COMPUTED STATE
        get currentStep() {
            return this.steps[this.currentIndex]
        },

        get currentDawProfile() {
            return this.dawProfiles[this.activeDawProfileKey]
        },

        // CONFIRMATION MODAL
        openConfirmModal({
            title,
            message,
            confirmLabel = 'Confirm',
            action,
        }) {
            this.confirmModal = {
                isOpen: true,
                title,
                message,
                confirmLabel,
                action,
            }
        },

        closeConfirmModal() {
            this.confirmModal.isOpen = false
            this.confirmModal.action = null
        },

        confirmModalAction() {
            const action = this.confirmModal.action

            this.closeConfirmModal()

            if (typeof action === 'function') {
                action()
            }
        },

        // NAVIGATION
        selectTab(tab) {
            const previousTab = this.activeTab

            this.activeTab = tab

            if (previousTab !== tab) {
                this.track('tab_viewed', {
                    tab,
                    previous_tab: previousTab,
                })
            }

            if (
                tab === 'sessions'
                || tab === 'exercises'
            ) {
                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        window.dispatchEvent(
                            new Event('picker:sync')
                        )
                    })
                })
            }
        },

        // PULSE
        async restorePulseSource() {
            const savedSource = this.getRememberedPulseSource()

            if (!savedSource) {
                return
            }

            const [origin, rawId] = savedSource.split(':')

            if (origin === 'preset') {
                const exists = this.pulsePresets.some(
                    preset => preset.id === Number(rawId)
                )

                if (!exists) {
                    localStorage.removeItem(
                        'pulse:selected-source'
                    )

                    return
                }
            }

            if (origin === 'user') {
                const exists = this.userPatterns.some(
                    pattern => pattern.id === Number(rawId)
                )

                if (!exists) {
                    localStorage.removeItem(
                        'pulse:selected-source'
                    )

                    return
                }
            }

            await this.selectPulseSource(savedSource)
        },
    }
}