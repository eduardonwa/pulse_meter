export function exercises() {
    return {
        // EXERCISE STATE
        activeExerciseIndex: null,
        maxSteps: 10,

        // EXERCISE FORM
        stepFormMode: 'create',
        stepFormIndex: null,
        isStepFormOpen: false,

        stepForm: {
            name: '',
            bpm: 100,
            mode: 'timer',
            duration_seconds: 60,
        },

        stepFormMinutes: 1,
        stepFormSeconds: 0,

        stepFormInitial: null,
        stepFormOpenedAt: null,

        // PLAYBACK FLOW
        autoAdvance: true,
        isWaitingForNextExercise: false,
        nextExerciseIndex: null,

        // PRACTICE REVIEW
        isPracticeReviewOpen: false,
        practiceFeeling: null,
        practiceFeelingConfirmation: '',
        
        getExerciseOrigin(step) {
            return step?.origin === 'custom'
                ? 'custom'
                : 'preset'
        },

        getStepFormPayload() {
            const duration =
            (Number(this.stepFormMinutes) * 60)
            + Number(this.stepFormSeconds)

            return {
                name: this.stepForm.name,
                bpm: Number(this.stepForm.bpm),
                mode: this.stepForm.mode,
                duration_seconds:
                    this.stepForm.mode === 'timer'
                        ? duration
                        : null
            }
        },

        getChangedStepFields(previous, current) {
            if (!previous) {
                return []
            }

            return [
                'name',
                'bpm',
                'mode',
                'duration_seconds',
            ].filter(field => {
                return previous[field] !== current[field]
            })
        },

        async removeStep(index) {
            if (this.steps.length <= 1) {
                return false
            }

            const step = this.steps[index]

            if (!step) {
                return false
            }

            if (this.usesServerPersistence) {
                if (!step.id) {
                    return false
                }

                const wasDeleted =
                    await this.destroyRoutineStep(step.id)

                if (!wasDeleted) {
                    return false
                }
            }

            this.steps.splice(index, 1)

            if (index < this.currentIndex) {
                this.currentIndex -= 1
            }

            if (this.currentIndex > this.steps.length - 1) {
                this.currentIndex = this.steps.length - 1
            }

            if (!this.usesServerPersistence) {
                this.saveToLocalStorage()
            }

            this.$nextTick(() => {
                window.dispatchEvent(
                    new Event('picker:sync')
                )
            })

            return true
        },

        resetStepForm() {
            this.stepFormMode = 'create'
            this.stepFormIndex = null

            this.stepForm = {
                name: `Exercise ${this.steps.length + 1}`,
                bpm: this.metronome.bpm,
                mode: 'timer',
                duration_seconds: 60,
            }

            this.stepFormMinutes = 1
            this.stepFormSeconds = 0

            this.stepFormInitial = this.getStepFormPayload()
        },

        // MODALS
        openAddStepModal() {
            if (this.steps.length >= this.maxSteps) {
                return
            }

            this.resetStepForm()
            this.openStepFormModal()
            
            /* TRACKEAR EVENTO */
            this.track('exercise_form_opened', {
                mode: 'create',
            })
        },

        openEditStepModal(index) {
            const step = this.steps[index]

            if (!step) { return }

            this.stepFormMode = 'edit'
            this.stepFormIndex = index

            this.stepForm = {
                name: step.name,
                bpm: Number(step.bpm),
                mode: step.mode,
                duration_seconds: Number(
                    step.duration_seconds ?? 60
                ),
            }

            this.stepFormMinutes = Math.floor(this.stepForm.duration_seconds / 60)
            this.stepFormSeconds = this.stepForm.duration_seconds % 60
            this.stepFormInitial = this.getStepFormPayload()

            this.openStepFormModal()

            this.track('exercise_form_opened', {
                mode: 'edit',
                exercise_origin: this.getExerciseOrigin(step),
                exercise_index: index,
            })
        },

        openStepFormModal() {
            this.stepFormOpenedAt = Date.now()

            this.$nextTick(() => {
                this.isStepFormOpen = true
                this.$refs.stepDialog.showModal()

                requestAnimationFrame(() => {
                    window.dispatchEvent(new Event('picker:sync'))
                })
            })
        },

        closeStepFormModal(trackCancellation = true) {
            if (trackCancellation && this.isStepFormOpen) {
                const currentPayload =
                    this.getStepFormPayload()

                const changedFields = 
                    this.getChangedStepFields(
                        this.stepFormInitial,
                        currentPayload
                    )

                const properties = {
                    mode: this.stepFormMode,
                    changed_fields: changedFields,
                    time_open_seconds: this.stepFormOpenedAt
                        ? Math.round(
                            (Date.now() - this.stepFormOpenedAt)
                            / 1000
                        )
                        : 0,
                }

                if (
                    this.stepFormMode === 'edit'
                    && this.stepFormIndex !== null
                ) {
                    properties.exercise_origin =
                        this.getExerciseOrigin(
                            this.steps[this.stepFormIndex]
                        )

                    properties.exercise_index =
                        this.stepFormIndex
                }

                if (changedFields.length > 0) {
                    this.track(
                        'exercise_edit_abandoned',
                        properties
                    )
                } else {
                    this.track(
                        'exercise_form_cancelled',
                        properties
                    )
                }
            }

            this.isStepFormOpen = false
            this.$refs.stepDialog.close()

            this.stepFormOpenedAt = null
        },

        openDeleteStepModal(index) {
            if (this.steps.length <= 1) {
                return
            }

            const step = this.steps[index]

            if (!step) {
                return
            }

            this.openConfirmModal({
                title: 'Delete exercise?',
                message:
                    `"${step.name}" will be permanently removed from this routine.`,
                confirmLabel: 'Delete',

                action: () => {
                    return this.removeStep(index)
                },
            })
        },
        
        async saveStepForm() {
            const payload = this.getStepFormPayload()

            let analyticsEvent = null
            let analyticsProperties = {}

            if (this.stepFormMode === 'edit') {
                if (this.stepFormIndex === null) {
                    return
                }

                const existingStep =
                    this.steps[this.stepFormIndex]

                if (!existingStep) {
                    return
                }

                const previousOrigin =
                    this.getExerciseOrigin(existingStep)

                const changedFields =
                    this.getChangedStepFields(
                        this.stepFormInitial,
                        payload
                    )

                let updatedStep

                if (this.usesServerPersistence) {
                    updatedStep =
                        await this.updateRoutineStep(
                            existingStep.id,
                            payload
                        )

                    if (!updatedStep) {
                        return
                    }
                } else {
                    updatedStep = {
                        ...existingStep,
                        ...payload,
                        origin: 'custom',
                    }
                }

                this.steps.splice(
                    this.stepFormIndex,
                    1,
                    updatedStep
                )

                if (
                    this.activeExerciseIndex === this.stepFormIndex
                    && this.isPlaying
                ) {
                    this.metronome.bpm = payload.bpm
                    this.startMetronome(payload.bpm)
                }

                if (changedFields.length > 0) {
                    analyticsEvent =
                        previousOrigin === 'preset'
                            ? 'exercise_customized'
                            : 'exercise_updated'

                    analyticsProperties = {
                        exercise_index: this.stepFormIndex,
                        previous_origin: previousOrigin,
                        exercise_origin: 'custom',
                        changed_fields: changedFields,
                        bpm: payload.bpm,
                        exercise_mode: payload.mode,
                        duration_seconds:
                            payload.duration_seconds,
                    }
                }
            } else {
                if (this.steps.length >= this.maxSteps) {
                    return
                }

                let createdStep

                if (this.usesServerPersistence) {
                    createdStep = await this.storeRoutineStep(payload)
                    
                    if (!createdStep) { return }
                } else {
                    createdStep = {
                        ...payload,
                        position: this.steps.length,
                        origin: 'custom'
                    }
                }

                this.steps.push(createdStep)

                analyticsEvent = 'exercise_created'

                analyticsProperties = {
                    exercise_index: this.steps.length - 1,
                    exercise_origin: createdStep.origin,
                    bpm: createdStep.bpm,
                    exercise_mode: createdStep.mode,
                    duration_seconds:
                        createdStep.duration_seconds,
                    exercise_count: this.steps.length,
                }
            }

            if (!this.usesServerPersistence) {
                this.saveToLocalStorage()
            }

            if (analyticsEvent) {
                this.track(
                    analyticsEvent,
                    analyticsProperties
                )
            }

            // false evita que guardar sea registrado
            // también como una cancelación.
            this.closeStepFormModal(false)

            this.$nextTick(() => {
                window.dispatchEvent(
                    new Event('picker:sync')
                )
            })

            this.resetStepForm()
        },
        
        // EXERCISES
        startExercise(index) {
            const step = this.steps[index]

            if (!step) { return }

            const configuredDuration = step.mode === 'timer' ? Number(step.duration_seconds ?? 60) : null

            /*
            * Esto pertenece al contexto del ejercicio,
            * no al motor general.
            */
            this.currentIndex = index
            this.activeTab = 'exercises'

            /*
            * Inicio compartido de cualquier reproducción.
            */
            this.startPlaybackSession({
                source: 'exercise',
                mode: step.mode,
                bpm: step.bpm,
                duration: configuredDuration,
                exerciseIndex: index,
            })

            /*
            * Tracking específico del ejercicio.
            */
            this.beginPlaybackTracking({
                source: 'exercise',

                metronome_mode: step.mode,
                bpm: Number(step.bpm),

                configured_duration_seconds: configuredDuration,

                exercise_index: index,
                exercise_origin: this.getExerciseOrigin(step),

                // Compatibilidad temporal
                exercise_mode: step.mode,
            })
        },

        updateExerciseBpm(index, bpm) {
            const step = this.steps[index]

            if (!step) {
                return
            }

            const nextBpm = Number(bpm)

            this.currentIndex = index

            // Actualiza inmediatamente la interfaz.
            this.steps[index].bpm = nextBpm
            this.steps[index].origin = 'custom'

            // Guarda el cambio.
            if (this.usesServerPersistence) {
                this.queueRoutineStepUpdate(
                    this.steps[index]
                )
            } else {
                this.saveToLocalStorage()
            }

            this.trackDebounced(
                `exercise-bpm-${step.id ?? index}`,
                'bpm_changed',
                {
                    source: 'exercise',
                    exercise_index: index,
                    exercise_origin: 'custom',
                    bpm: nextBpm,
                },
                700
            )

            if (
                this.activeExerciseIndex === index
                && this.isPlaying
            ) {
                this.metronome.bpm = nextBpm

                this.startMetronome(
                    this.metronome.bpm
                )
            }
        },

        updateExerciseName(index, name) {
            const step = this.steps[index]

            if (!step) {
                return
            }

            this.steps[index].name = String(name)
            this.steps[index].origin = 'custom'

            if (this.usesServerPersistence) {
                this.queueRoutineStepUpdate(
                    this.steps[index]
                )
            } else {
                this.saveToLocalStorage()
            }
        },

        formatTime(seconds) {
            const value = Number(seconds ?? 0)

            const minutes = Math.floor(value / 60)
            const remainingSeconds = value % 60

            return `${minutes}:${String(remainingSeconds).padStart(2, '0')}`
        },

        getStepTimeLabel(step, index) {
            if (!step) {
                return ''
            }

            if (step.mode === 'classic') {
                return 'Classic'
            }

            if (this.activeExerciseIndex === index && this.isPlaying) {
                return this.formatTime(this.remaining ?? step.duration_seconds)
            }

            return this.formatTime(step.duration_seconds)
        },

        getActiveExerciseName() {
            if (this.activeExerciseIndex === null) {
                return ''
            }

            return this.steps[this.activeExerciseIndex]?.name ?? ''
        },

        getActiveExerciseTimeLabel() {
            if (this.activeExerciseIndex === null) {
                return ''
            }

            const step = this.steps[this.activeExerciseIndex]

            if (!step) {
                return ''
            }

            return this.getStepTimeLabel(step, this.activeExerciseIndex)
        },

        finishExerciseSession() {
            const completedStep = this.steps[this.activeExerciseIndex]
            
            if (completedStep) {
                this.track('exercise_completed', {
                    source: 'exercise',
                    exercise_index: this.activeExerciseIndex,
                    exercise_origin: this.getExerciseOrigin(completedStep),
                    exercise_mode: completedStep.mode,
                    bpm: Number(completedStep.bpm),
                    duration_seconds:
                        completedStep.mode === 'timer'
                            ? Number(
                                completedStep.duration_seconds ?? 60
                            )
                            : null,
                    auto_advance: Boolean(this.autoAdvance),
                })
            }

            this.playFinishSound()

            if (!this.autoAdvance) {
                this.stop('completed')
                return
            }

            const nextIndex =
                this.activeExerciseIndex + 1

            if (nextIndex >= this.steps.length) {
                this.stop()
                this.openPracticeReviewModal()
                return
            }

            this.stop('completed')

            this.nextExerciseIndex = nextIndex
            this.isWaitingForNextExercise = true

            this.$nextTick(() => { document.activeElement?.blur() })
        },

        continueToNextExercise() {
            if (!this.isWaitingForNextExercise) {
                return
            }

            if (this.nextExerciseIndex === null) {
                return
            }

            const index = this.nextExerciseIndex

            this.isWaitingForNextExercise = false
            this.nextExerciseIndex = null

            this.startExercise(index)
        },

        openPracticeReviewModal() {
            this.isWaitingForNextExercise = false
            this.nextExerciseIndex = null

            this.practiceFeeling = null
            this.practiceFeelingConfirmation = ''
            this.isPracticeReviewOpen = true

            this.$nextTick(() => {
                document.activeElement?.blur()
            })
        },

        selectPracticeFeeling(value) {
            this.practiceFeeling = value

            const confirmation = {
                estranged: 'Some days feel detached. Still counts.',
                sad: 'Heavy day. You still showed up.',
                happy: 'Good. Keep that energy.',
                optimistic: 'Nice. That means something is clicking.',
            }
            
            this.practiceFeelingConfirmation = confirmation[value] ?? 'Logged'
        },

        closePracticeReviewModal() {
            this.isPracticeReviewOpen = false
        }
    }
}