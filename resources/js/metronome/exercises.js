export function exercises() {
    return {
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

        removeStep(index) {
            if (this.steps.length <= 1) {
                return
            }

            this.steps.splice(index, 1)

            if (this.currentIndex > this.steps.length - 1) {
                this.currentIndex = this.steps.length - 1
            }

            this.saveToLocalStorage()

            this.$nextTick(() => {
                window.dispatchEvent(new Event('picker:sync'))
            })
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
        
        saveStepForm() {
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

                this.steps[this.stepFormIndex] = {
                    ...existingStep,
                    ...payload,

                    // Después de guardar un preset editado,
                    // ya cuenta como ejercicio personalizado.
                    origin: 'custom',
                }

                if (
                    this.activeExerciseIndex
                        === this.stepFormIndex
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

                this.steps.push({
                    ...payload,
                    origin: 'custom',
                })

                analyticsEvent = 'exercise_created'

                analyticsProperties = {
                    exercise_index: this.steps.length - 1,
                    exercise_origin: 'custom',
                    bpm: payload.bpm,
                    exercise_mode: payload.mode,
                    duration_seconds:
                        payload.duration_seconds,
                    exercise_count: this.steps.length,
                }
            }

            this.saveToLocalStorage()

            if (analyticsEvent) {
                this.track(
                    analyticsEvent,
                    analyticsProperties
                )
            }
            
            // false evita que guardar sea registrado
            // // también como una cancelación.
            this.closeStepFormModal(false)

            this.$nextTick(() => {
                window.dispatchEvent(
                    new Event('picker:sync')
                )
            })

            this.resetStepForm()
        },

        startExercise(index) {
            const step = this.steps[index]
            
            if (!step) { return }

            const configuredDuration = step.mode === 'timer'
                ? Number(step.duration_seconds ?? 60)
                : null

            this.stop('replaced')

            this.activeSessionType = 'exercise'

            this.currentIndex = index
            this.activeExerciseIndex = index
            this.activeTab = 'exercises'

            this.metronome.bpm = step.bpm
            this.metronome.mode = step.mode
            this.metronome.duration_seconds = configuredDuration

            this.ensureAudioContext()
            this.isPlaying = true

            this.startMetronome(this.metronome.bpm)
            
            this.beginPlaybackTracking({
                source: 'exercise',
                exercise_index: index,
                exercise_origin: this.getExerciseOrigin(step),
                exercise_mode: step.mode,
                bpm: Number(step.bpm),
                configured_duration_seconds: configuredDuration,
            })
            
            if (
                this.metronome.mode === 'timer'
                && configuredDuration
            ) { this.startTimer(configuredDuration) }
        },

        updateExerciseBpm(index, bpm) {
            const step = this.steps[index]
            
            if (!step) { return }

            const nextBpm = Number(bpm)

            this.currentIndex = index
            this.steps[index].bpm = nextBpm

            this.saveToLocalStorage()

            this.trackDebounced(
                `exercise-bpm-${index}`,
                'bpm_changed',
                {
                    source: 'exercise',
                    exercise_index: index,
                    exercise_origin:
                        this.getExerciseOrigin(step),
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

            if (step.mode === 'manual') {
                return 'Manual'
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