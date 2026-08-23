export function alphaTabExerciseControls() {
    return {
        detailsOpen: false,
        looping: false,
        hearing: false,

        syncPlaybackState(event, element) {
            if (event.detail?.element !== element) {
                return
            }

            this.hearing = Boolean(
                event.detail?.playing
            )
        },

        playPause(element) {
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

        toggleLoop(element) {
            this.looping = !this.looping

            window.dispatchEvent(
                new CustomEvent(
                    'alphatab:toggle-loop',
                    {
                        detail: {
                            element,
                        },
                    }
                )
            )
        },

        toggleDetails(element, nextTick) {
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

            window.dispatchEvent(
                new Event('alphatab:stop')
            )
        },

        clearActiveExercise() {
            this.detailsOpen = false
        },

        syncActiveExercise(event, index, hasPattern) {
            const isActive =
                Number(event.detail?.index) === Number(index)

            this.detailsOpen =
                isActive && Boolean(hasPattern)
        },
    }
}