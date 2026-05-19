window.routinePlayer = function (steps) {
    return {
        steps,
        storageKey: 'pulse_meter_routine',

        init() {
            this.loadFromLocalStorage()

            this.$watch('steps', () => {
                this.saveToLocalStorage()
            })
        },

        saveToLocalStorage() {
            localStorage.setItem(this.storageKey, JSON.stringify(this.steps))
        },

        loadFromLocalStorage() {
            const saved = localStorage.getItem(this.storageKey)

            if (saved) {
                this.steps = JSON.parse(saved)
            }
        },

        currentIndex: 0,
        isPlaying: false,
        audioContext: null,
        intervalId: null,
        timerId: null,
        remaining: null,
        autoAdvance: true,
        nextKey: 'ArrowRight',
        previousKey: 'ArrowLeft',
        maxSteps: 10,
        newStep: {
            name: '',
            bpm: 100,
            mode: 'timer',
            duration_seconds: 60
        },
        newStepMinutes: 1,
        newStepSeconds: 0,

        get currentStep() {
            return this.steps[this.currentIndex]
        },

        get formattedRemaining() {
            if (this.remaining === null) {
                return '--:--'
            }

            const minutes = Math.floor(this.remaining / 60)
            const seconds = this.remaining % 60

            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
        },

        get durationMinutes() {
            return Math.floor(this.currentStep.duration_seconds / 60)
        },

        set durationMinutes(value) {
            const seconds = this.durationSeconds
            this.currentStep.duration_seconds = (value * 60) + seconds
        },

        get durationSeconds() {
            return this.currentStep.duration_seconds % 60
        },

        set durationSeconds(value) {
            const minutes = this.durationMinutes
            this.currentStep.duration_seconds = (minutes * 60) + value
        },

        updateDurationFromInputs() {
            if (this.durationSeconds > 59) {
                this.durationSeconds = 59
            }

            if (this.durationSeconds < 0) {
                this.durationSeconds = 0
            }

            if (this.currentStep.duration_seconds > 300) {
                this.currentStep.duration_seconds = 300
            }

            if (this.currentStep.duration_seconds < 1) {
                this.currentStep.duration_seconds = 1
            }
        },

        toggle() {
            this.isPlaying ? this.stop() : this.start()
        },

        start() {
            this.audioContext ??= new AudioContext()
            this.isPlaying = true

            this.startMetronome()

            if (this.currentStep.mode == 'timer') {
                this.startTimer()
            }
        },

        stop() {
            clearInterval(this.intervalId)
            clearInterval(this.timerId)
            this.isPlaying = false
        },

        startMetronome() {
            clearInterval(this.intervalId)

            this.tick()

            this.intervalId = setInterval(() => {
                this.tick()
            }, 60000 / this.currentStep.bpm)
        },

        tick() {
            const osc = this.audioContext.createOscillator()
            const gain = this.audioContext.createGain()

            osc.frequency.value = 900
            gain.gain.setValueAtTime(1, this.audioContext.currentTime)
            gain.gain.exponentialRampToValueAtTime(
                0.001,
                this.audioContext.currentTime + 0.05
            )

            osc.connect(gain)
            gain.connect(this.audioContext.destination)

            osc.start(this.audioContext.currentTime)
            osc.stop(this.audioContext.currentTime + 0.05)
        },

        startTimer() {
            clearInterval(this.timerId)

            this.remaining = this.currentStep.duration_seconds

            this.timerId = setInterval(() => {
                this.remaining--

                if (this.remaining <= 0) {
                    this.finishStep()
                }
            }, 1000)
        },

        nextStep(keepPlaying = true) {
            if (this.currentIndex < this.steps.length - 1) {
                this.currentIndex++

                if (keepPlaying) {
                    this.restartCurrentStep()
                } else {
                    this.remaining = this.currentStep.duration_seconds
                }
            }
        },

        previousStep() {
            if (this.currentIndex > 0) {
                this.currentIndex--
                this.restartCurrentStep()
            }
        },

        restartCurrentStep() {
            const wasPlaying = this.isPlaying
            this.stop()

            if(wasPlaying) {
                this.start()
            }
        },

        finishStep() {
            this.stop()
            this.playFinishSound()

            if (this.autoAdvance) {
                this.nextStep(false)
            }
        },

        changeMode() {
            if (this.currentStep.mode === 'timer' && !this.currentStep.duration_seconds) {
                this.currentStep.duration_seconds = 60
            }

            this.restartCurrentStep()
        },

        playFinishSound() {
            this.audioContext ??= new AudioContext()

            const osc = this.audioContext.createOscillator()
            const gain = this.audioContext.createGain()

            osc.frequency.value = 1300

            gain.gain.setValueAtTime(1, this.audioContext.currentTime)
            gain.gain.exponentialRampToValueAtTime(
                0.001,
                this.audioContext.currentTime + 0.25
            )

            osc.connect(gain)
            gain.connect(this.audioContext.destination)

            osc.start(this.audioContext.currentTime)
            osc.stop(this.audioContext.currentTime + 0.25)
        },

        handleHotKey(event) {
            if (event.key === this.nextKey) {
                this.nextStep()
            }

            if (event.key === this.previousKey) {
                this.previousStep()
            }
        },

        clampDuration() {
            if (this.currentStep.duration_seconds > 300) {
                this.currentStep.duration_seconds = 300
            }

            if (this.currentStep.duration_seconds < 1) {
                this.currentStep.duration_seconds = 1
            }
        },

        addStep() {
            if (this.steps.length >=  this.maxSteps) {
                return
            }

            this.steps.push({
                name: `Ejercicio ${this.steps.length + 1}`,
                bpm: 100,
                mode: 'manual',
                duration_seconds: null
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

            this.stop()
        },

        openAddStepModal() {
            if (this.steps.length >= this.maxSteps) {
                return
            }

            this.newStep = {
                name: `Ejercicio ${this.steps.length + 1}`,
                bpm: this.currentStep?.bpm ?? 100,
                mode: 'timer',
                duration_seconds: 60
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
                duration_seconds: duration
            })

            this.$refs.addStepDialog.close()
        },
    }
}