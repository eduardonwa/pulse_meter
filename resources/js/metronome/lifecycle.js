import { isTypingInField } from '../helpers.js'

export function lifecycle() {
    return {
        // INITIALIZATION
        async init() {
            // Show the real panel only once Alpine has rendered its initial state.
            // Do not wait for the optional server calls below.
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    const panel = this.$root.querySelector('[data-practice-content]')
                    const loading = this.$root.querySelector('[data-practice-loading]')
                    if (panel) {
                        panel.style.removeProperty('visibility')
                        panel.style.removeProperty('height')
                        panel.style.removeProperty('overflow')
                        panel.style.removeProperty('pointer-events')
                    }
                    loading?.remove()

                    this.$root.dataset.practiceReady = 'true'

                    window.dispatchEvent(new Event('picker:sync'))

                    if (performance.getEntriesByName('dorelog:practice-bootstrap-start', 'mark').length) {
                        performance.mark('dorelog:practice-panel-ready')
                        performance.measure(
                            'dorelog:practice-initialization',
                            'dorelog:practice-bootstrap-start',
                            'dorelog:practice-panel-ready'
                        )
                    }

                    if (
                        performance.getEntriesByName(
                            'dorelog:livewire-start-returned',
                            'mark'
                        ).length
                    ) {
                        performance.measure(
                            'dorelog:practice-panel-render',
                            'dorelog:livewire-start-returned',
                            'dorelog:practice-panel-ready'
                        )
                    }
                })
            })

            if (this.activeTab === 'sessions' && this.canUseCreativeSessions) {
                this.handleSessionModeChange('creative')
            }
            if (this.usesLocalPersistence) {
                this.loadFromLocalStorage()

                this.$watch('steps', () => {
                    this.saveToLocalStorage()
                })
            }

            this.loadRecentSessions()
            this.loadClickSounds?.()

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

            if (this.activeTab === 'sessions' && this.canUseCreativeSessions) {
                this.toggle()
            } else if (this.practiceMode === 'routine') {
                this.toggleRoutinePlayback()
            }
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
            if (tab === 'sessions' && !this.canUseCreativeSessions) {
                return
            }
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
