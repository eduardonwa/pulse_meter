import { isTypingInField } from '../helpers'
import { logAudioLifecycle } from './audio-engine'

export function lifecycle() {
    return {
        // INITIALIZATION
        async init() {
            logAudioLifecycle('init', { audioState: this.audioContext?.state ?? null })
            this.audioPagehideListener = event => {
                logAudioLifecycle('pagehide', {
                    persisted: event.persisted,
                    audioState: this.audioContext?.state ?? null,
                })
            }
            this.audioPageshowListener = event => {
                logAudioLifecycle('pageshow', {
                    persisted: event.persisted,
                    audioState: this.audioContext?.state ?? null,
                })
            }
            window.addEventListener('pagehide', this.audioPagehideListener)
            window.addEventListener('pageshow', this.audioPageshowListener)
            if (this.usesLocalPersistence) {
                this.loadFromLocalStorage()

                this.$watch('steps', () => {
                    this.saveToLocalStorage()
                })
            }

            this.loadRecentSessions()
            this.loadClickSounds?.()

            this.audioVisibilityListener = () => {
                logAudioLifecycle('visibilitychange', {
                    audioState: this.audioContext?.state ?? null,
                    playing: this.isPlaying,
                })
                if (document.hidden) this.cancelAudioRecovery?.('hidden')
                else this.scheduleAudioRecovery?.()
            }
            document.addEventListener('visibilitychange', this.audioVisibilityListener)

            if (this.usesServerPersistence) {
                await this.loadPulsePatterns?.()
                await this.loadRandomizedSessions?.()
            }

            await this.restorePulseSource()
            
            this.setPulseBaseline?.()

            this.$nextTick(() => {
                if (this.usesServerPersistence) {
                    this.prepareLocalRoutineImport?.()
                }
            })
        },

        destroy() {
            logAudioLifecycle('destroy', { audioState: this.audioContext?.state ?? null })
            window.removeEventListener('pagehide', this.audioPagehideListener)
            window.removeEventListener('pageshow', this.audioPageshowListener)
            this.cancelAudioRecovery?.('destroy')
            document.removeEventListener('visibilitychange', this.audioVisibilityListener)
            this.stop?.('navigation')
        },

        // GLOBAL SHORCUTS
        handleKeydown(event) {
            if (event.key === 'Escape') {
                if (event.repeat) {
                    return
                }

                if (this.closeActiveModal()) {
                    event.preventDefault()
                    return
                }

                if (this.toolTether?.active) {
                    this.cancelToolTether()
                    return
                }

                return
            }

            if (isTypingInField(event)) {
                return
            }

            if (event.code !== 'Space') {
                return
            }

            const isRoutinePage =
                /^\/(?:[a-z]{2}(?:-[a-z]{2})?\/)?routines(?:\/|$)/i
                    .test(window.location.pathname)

            if (isRoutinePage) {
                event.preventDefault()
                event.stopPropagation()

                if (!event.repeat && this.isWaitingForNextExercise) {
                    this.continueToNextExercise()
                }

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

        closeActiveModal() {
            // El modal de confirmación tiene prioridad.
            if (this.confirmModal?.isOpen) {
                if (!this.confirmModal.isProcessing) {
                    this.closeConfirmModal()
                }

                return true
            }

            // Modal de reset.
            if (this.showResetAppModal) {
                this.closeResetAppModal()
                return true
            }

            // Modal para crear o editar ejercicios.
            if (this.isStepFormOpen) {
                this.closeStepFormModal()
                return true
            }

            return false
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
