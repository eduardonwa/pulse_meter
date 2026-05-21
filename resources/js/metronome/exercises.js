export function exercises() {
    return {
        removeCurrentStep() {
            if (this.steps.length <= 1) {
                return
            }

            this.steps.splice(this.currentIndex, 1)

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
        },

        openAddStepModal() {
            if (this.steps.length >= this.maxSteps) {
                return
            }

            this.resetStepForm()

            this.$nextTick(() => {
                this.$refs.stepDialog.showModal()
            })
        },

        openEditStepModal(index) {
            const step = this.steps[index]

            this.stepFormMode = 'edit'
            this.stepFormIndex = index

            this.stepForm = {
                name: step.name,
                bpm: Number(step.bpm),
                mode: step.mode,
                duration_seconds: Number(step.duration_seconds ?? 60),
            }

            this.stepFormMinutes = Math.floor(this.stepForm.duration_seconds / 60)
            this.stepFormSeconds = this.stepForm.duration_seconds % 60

            this.$nextTick(() => {
                this.$refs.stepDialog.showModal()
            })
        },

        saveStepForm() {
            const duration = (Number(this.stepFormMinutes) * 60) + Number(this.stepFormSeconds)

            const payload = {
                name: this.stepForm.name,
                bpm: Number(this.stepForm.bpm),
                mode: this.stepForm.mode,
                duration_seconds: this.stepForm.mode === 'timer' ? duration : null,
            }

            if (this.stepFormMode === 'edit') {
                if (this.stepFormIndex === null) {
                    return
                }

                this.steps[this.stepFormIndex] = {
                    ...this.steps[this.stepFormIndex],
                    ...payload,
                }

                if (this.activeExerciseIndex === this.stepFormIndex && this.isPlaying) {
                    this.metronome.bpm = payload.bpm
                    this.startMetronome(payload.bpm)
                }
            } else {
                if (this.steps.length >= this.maxSteps) {
                    return
                }

                this.steps.push(payload)
            }

            this.saveToLocalStorage()

            this.$refs.stepDialog.close()

            this.$nextTick(() => {
                window.dispatchEvent(new Event('picker:sync'))
            })

            this.resetStepForm()
        },

        startExercise(index) {
            const step = this.steps[index]

            this.stop()

            this.currentIndex = index
            this.activeExerciseIndex = index
            this.activeTab = 'exercises'

            this.metronome.bpm = step.bpm
            this.metronome.mode = step.mode
            this.metronome.duration_seconds = step.duration_seconds ?? 60

            this.ensureAudioContext()
            this.isPlaying = true

            this.startMetronome(this.metronome.bpm)

            if (this.metronome.mode === 'timer' && this.metronome.duration_seconds) {
                this.startTimer(this.metronome.duration_seconds)
            }
        },

        updateExerciseBpm(index, bpm) {
            this.currentIndex = index
            this.steps[index].bpm = Number(bpm)

            this.saveToLocalStorage()

            if (this.activeExerciseIndex === index && this.isPlaying) {
                this.metronome.bpm = Number(bpm)
                this.startMetronome(this.metronome.bpm)
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
    }
}