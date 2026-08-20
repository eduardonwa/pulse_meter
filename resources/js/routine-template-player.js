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
                mode: step.mode,

                duration_seconds:
                    step.mode === 'timer'
                        ? Number(step.duration_seconds)
                        : null,
            }))

            localStorage.setItem(
                'pulse_meter_routine',
                JSON.stringify(localSteps)
            )

            const url =
                this.$root.dataset.useTemplateUrl

            if (url) {
                window.location.href = url
            }
        },

        async saveTemplateCopy() {
            const url =
                this.$root.dataset.saveTemplateCopyUrl

            if (!url) {
                return
            }

            const title =
                this.$root.dataset.templateTitle
                ?? 'Routine'

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
                        mode: step.mode,

                        duration_seconds:
                            step.mode === 'timer'
                                ? Number(
                                    step.duration_seconds
                                )
                                : null,
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

            window.location.href =
                data.redirect_url
        },
    }
}