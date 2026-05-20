export function exercises() {
    return {
        addStep() {
            if (this.steps.length >= this.maxSteps) {
                return
            }

            this.steps.push({
                name: `Exercise #${this.steps.length + 1}`,
                bpm: 100,
                mode: 'manual',
                duration_seconds: null,
            })
        },

        removeCurrentStep() {
            if (this.steps.length <= 1) {
                return
            }

            this.steps.splice(this.currentIndex, 1)

            if (this.currentIndex > this.steps.length - 1) {
                this.currentIndex = this.steps.length - 1
            }
        },

        openAddStepModal() {
            if (this.steps.length >= this.maxSteps) {
                return
            }

            this.newStep = {
                name: `Ejercicio ${this.steps.length + 1}`,
                bpm: this.metronome.bpm,
                mode: 'timer',
                duration_seconds: 60,
            }

            this.newStepMinutes = 1
            this.newStepSeconds = 0

            this.$refs.addStepDialog.showModal()
        },

        saveNewStep() {
            if (this.steps.length >= this.maxSteps) {
                return
            }

            let duration = null

            if (this.newStep.mode === 'timer') {
                duration = (this.newStepMinutes * 60) + this.newStepSeconds

                if (duration > 300) duration = 300
                if (duration < 1) duration = 1
            }

            this.steps.push({
                name: this.newStep.name || `Ejercicio ${this.steps.length + 1}`,
                bpm: this.newStep.bpm || 100,
                mode: this.newStep.mode,
                duration_seconds: duration,
            })

            this.$refs.addStepDialog.close()
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

            if (this.activeExerciseIndex === index && this.isPlaying) {
                this.metronome.bpm = Number(bpm)
                this.startMetronome(this.metronome.bpm)
            }
        },
    }
}