let resumeViewerOnNextExercise = false
let resumeViewerFullscreen = false

export function alphaTabExerciseControls() {
    return {
        detailsOpen: false,
        hearing: false,
        scoreLoading: false,
        viewFullscreen: false,

        syncScoreRendered(event, element) {
            if (event.detail?.element === element) this.scoreLoading = false
        },

        syncPlaybackState(event, element) {
            if (event.detail?.element !== element) {
                return
            }

            this.hearing = Boolean(
                event.detail?.playing
            )
        },

        playPause(element) {
            if (this.isPlaying && this.playbackSource === 'exercise') {
                this.stop('manual')
            }

            window.dispatchEvent(
                new CustomEvent(
                    'alphatab:play-pause',
                    {
                        detail: {
                            element,
                        },
                    }
                )
            )
        },

        stopPlayback() {
            if (this.isPlaying && this.playbackSource === 'exercise') {
                this.stop('manual')
                return
            }

            window.dispatchEvent(
                new Event('alphatab:stop')
            )
        },

        toggleDetails(element, nextTick, stopOnClose = true) {
            this.detailsOpen = !this.detailsOpen

            if (this.detailsOpen) {
                nextTick(() => {
                    window.dispatchEvent(
                        new CustomEvent('alphatab:mount', {
                            detail: { element },
                        })
                    )

                    requestAnimationFrame(() => {
                        window.dispatchEvent(
                            new CustomEvent('alphatab:render', {
                                detail: { element },
                            })
                        )
                    })
                })

                return
            }

            if (stopOnClose) {
                window.dispatchEvent(
                    new Event('alphatab:stop')
                )
            }
        },

        startMobileExercisePractice(index, step, dialog, content, home, element, nextTick) {
            if (this.activeExerciseIndex === index && this.isPlaying) {
                this.stop()
                return
            }

            if (this.hasAlphaTabPattern(step)) {
                this.openMobileExerciseView(dialog, content, home, element, nextTick)
            }

            this.startExercise(index)
        },

        toggleExerciseViewSize(element, nextTick) {
            this.viewFullscreen = !this.viewFullscreen
            nextTick(() => {
                requestAnimationFrame(() => {
                    window.dispatchEvent(new CustomEvent('alphatab:render', { detail: { element } }))
                })
            })
        },

        openMobileExerciseView(dialog, content, home, element, nextTick, fullscreen = false) {
            if (!dialog?.showModal || !content) return

            // Move the existing notation; never duplicate the alphaTab host/API.
            const notation = home?.querySelector('.exercise-row__notation')
            if (notation) content.appendChild(notation)

            this.viewFullscreen = fullscreen
            this.scoreLoading = Boolean(element && element.dataset.alphaTabRendered !== 'true')
            this.detailsOpen = true
            dialog.showModal()

            if (element) {
                nextTick(() => {
                    window.dispatchEvent(new CustomEvent('alphatab:mount', {
                        detail: { element },
                    }))
                    requestAnimationFrame(() => {
                        window.dispatchEvent(new CustomEvent('alphatab:render', {
                            detail: { element },
                        }))
                    })
                })
            }
        },

        closeMobileExerciseView(home) {
            const content = this.$refs?.exerciseViewContent
            const notation = content?.querySelector('.exercise-row__notation')
            if (notation && home) home.appendChild(notation)
            this.detailsOpen = false
            this.viewFullscreen = false
            // Closing the viewer does not stop an ongoing practice session.
        },

        clearActiveExercise(event) {
            // Native modal dialogs remain in the browser's top layer and
            // otherwise conceal the Continue and review overlays.
            const dialog = this.$refs?.exerciseViewDialog
            if (dialog?.open && event?.detail?.resumeOnNext) {
                resumeViewerOnNextExercise = true
                resumeViewerFullscreen = this.viewFullscreen
            } else if (!event?.detail?.resumeOnNext) {
                resumeViewerOnNextExercise = false
                resumeViewerFullscreen = false
            }
            if (dialog?.open) dialog.close()
            this.detailsOpen = false
        },

        syncActiveExercise(event, index, hasPattern) {
            const isActive =
                Number(event.detail?.index) === Number(index)

            if (!isActive) return

            if (resumeViewerOnNextExercise) {
                const fullscreen = resumeViewerFullscreen
                resumeViewerOnNextExercise = false
                resumeViewerFullscreen = false
                if (hasPattern) {
                    this.openMobileExerciseView(
                        this.$refs.exerciseViewDialog,
                        this.$refs.exerciseViewContent,
                        this.$refs.exerciseDetails,
                        this.$refs.alphaTab,
                        this.$nextTick.bind(this),
                        fullscreen
                    )
                }
            }
        },
    }
}