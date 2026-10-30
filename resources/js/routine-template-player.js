export function routineTemplateActions() {
    return {
        useTemplateAsLocalRoutine() {
            this.openConfirmModal({
                title: 'Replace your current routine?',

                message:
                    'This routine will replace the one currently saved in Free Mode.',

                confirmLabel: 'Use this routine',

                action: () => {
                    this.replaceLocalRoutineWithTemplate()
                },
            })
        },

        replaceLocalRoutineWithTemplate() {
            const localSteps = this.steps.map(step => ({
                name: step.name,
                bpm: Number(step.bpm),
                mode: step.mode === 'timer' ? 'drill' : step.mode,

                duration_seconds:
                    ['timer', 'drill'].includes(step.mode)
                        ? Number(step.duration_seconds)
                        : null,
                
                alpha_tex: step.alpha_tex ?? null,
                time_signature_numerator: step.time_signature_numerator ?? 4,
                time_signature_denominator: step.time_signature_denominator ?? 4,
            }))

            localStorage.setItem(
                'pulse_meter_routine',
                JSON.stringify(localSteps)
            )

            const url =
                this.$el
                    .closest('.routine-player')
                    ?.dataset.useTemplateUrl

            if (url) {
                window.location.href = url
            }
        },

        async saveSharedCopy() {
            const url = this.$el.closest('.routine-player')?.dataset.saveTemplateCopyUrl
            if (!url) return
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            })
            if (!response.ok) return
            const data = await response.json()
            window.location.href = data.redirect_url
        },

        async saveTemplateCopy() {
            const player =
                this.$el.closest('.routine-player')

            const url =
                player?.dataset.saveTemplateCopyUrl

            if (!url) {
                return
            }

            const title = player?.dataset.templateTitle ?? 'Routine'

            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',

                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',

                    'X-CSRF-TOKEN': document
                        .querySelector(
                            'meta[name="csrf-token"]'
                        )
                        ?.content ?? '',
                },

                body: JSON.stringify({
                    name: title,

                    steps: this.steps.map(step => ({
                        name: step.name,
                        bpm: Number(step.bpm),
                        mode: step.mode === 'timer' ? 'drill' : step.mode,

                        duration_seconds:
                            ['timer', 'drill'].includes(step.mode)
                                ? Number(step.duration_seconds)
                                : null,
                        
                        alpha_tex: step.alpha_tex ?? null,
                    })),
                }),
            })

            const data = await response
                .json()
                .catch(() => ({}))

            if (!response.ok) {
                console.error(
                    'Could not save routine copy.',
                    data
                )

                return
            }

            window.location.href = data.redirect_url
        },

        previewTemplateExercise(index, element) {
            if (!element) return

            this.currentIndex = index
            this.activeExerciseIndex = index

            window.dispatchEvent(
                new CustomEvent('routine:exercise-active', {
                    detail: { index },
                })
            )

            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    window.dispatchEvent(
                        new CustomEvent('alphatab:play-pause', {
                            detail: { element },
                        })
                    )
                })
            })
        }
    }
}