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
    }
}