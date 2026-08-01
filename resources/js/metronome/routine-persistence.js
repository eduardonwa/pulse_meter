export function routinePersistence() {
    return {
        routineStepUpdateTimeouts: {},

        async storeRoutineStep(payload) {
            console.log('Sending exercise payload:', payload)

            const response = await fetch(
                this.$root.dataset.routineStepsStoreUrl,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',

                        'X-CSRF-TOKEN': document
                            .querySelector(
                                'meta[name="csrf-token"]'
                            )
                            .content,
                    },

                    body: JSON.stringify(payload),
                }
            )

            if (!response.ok) {
                console.error(
                    'Could not create exercise.',
                    await response.json()
                )

                this.showToast?.(
                    'Could not create exercise',
                    'error'
                )

                return null
            }

            return await response.json()
        },

        async updateRoutineStep(id, payload) {
            const playerRoot = this.$root.closest(
                '[data-routine-step-update-url]'
            )

            const urlTemplate =
                playerRoot?.dataset.routineStepUpdateUrl

            if (!urlTemplate) {
                console.error(
                    'Routine step update URL was not found.'
                )

                return null
            }

            const url = urlTemplate.replace('__ID__', id)

            const response = await fetch(url, {
                method: 'PATCH',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',

                    'X-CSRF-TOKEN': document
                        .querySelector(
                            'meta[name="csrf-token"]'
                        )
                        .content,
                },

                body: JSON.stringify(payload),
            })

            const data = await response.json()

            if (!response.ok) {
                console.error(
                    'Could not update exercise.',
                    data
                )

                return null
            }

            return data
        },

        queueRoutineStepUpdate(step, delay = 700) {
            if (!step?.id) {
                return
            }

            const stepId = step.id

            clearTimeout(
                this.routineStepUpdateTimeouts[stepId]
            )

            this.routineStepUpdateTimeouts[stepId] =
                setTimeout(async () => {
                    const currentStep = this.steps.find(
                        item => item.id === stepId
                    )

                    if (!currentStep) {
                        return
                    }

                    const payload = {
                        name: currentStep.name.trim(),
                        bpm: Number(currentStep.bpm),
                        mode: currentStep.mode,

                        duration_seconds:
                            currentStep.mode === 'timer'
                                ? Number(currentStep.duration_seconds)
                                : null,
                    }

                    await this.updateRoutineStep(
                        stepId,
                        payload
                    )

                    delete this.routineStepUpdateTimeouts[
                        stepId
                    ]
                }, delay)
        },

        async destroyRoutineStep(id) {
            const playerRoot = this.$root.closest(
                '[data-routine-step-destroy-url]'
            )

            const urlTemplate =
                playerRoot?.dataset.routineStepDestroyUrl

            if (!urlTemplate) {
                console.error(
                    'Routine step destroy URL was not found.'
                )

                return false
            }

            /*
            * Evita que un PATCH pendiente del nombre o BPM
            * se ejecute después de borrar el ejercicio.
            */
            if (this.routineStepUpdateTimeouts?.[id]) {
                clearTimeout(
                    this.routineStepUpdateTimeouts[id]
                )

                delete this.routineStepUpdateTimeouts[id]
            }

            const url = urlTemplate.replace('__ID__', id)

            const response = await fetch(url, {
                method: 'DELETE',

                headers: {
                    Accept: 'application/json',

                    'X-CSRF-TOKEN': document
                        .querySelector(
                            'meta[name="csrf-token"]'
                        )
                        .content,
                },
            })

            if (!response.ok) {
                const data = await response
                    .json()
                    .catch(() => ({}))

                console.error(
                    'Could not delete exercise.',
                    data
                )

                return false
            }

            return true
        },
    }
}