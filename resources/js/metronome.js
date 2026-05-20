window.routinePlayer = function (steps) {
    return {
        steps,
        storageKey: 'pulse_meter_routine',

        metronome: {
            bpm: 100,
            mode: 'timer',
            duration_seconds: 60,
        },

        currentIndex: 0,
        activeTab: 'types',

        isPlaying: false,
        audioContext: null,
        intervalId: null,
        timerId: null,
        remaining: null,

        activeExerciseIndex: null,
        autoAdvance: true,
        maxSteps: 5,

        beatsPerMeasure: 4,
        currentBeat: 1,

        minutesOptions: [0, 1, 2, 3, 4, 5],
        secondsOptions: Array.from({ length: 60 }, (_, i) => i),

        newStep: {
            name: '',
            bpm: 100,
            mode: 'timer',
            duration_seconds: 60,
        },

        newStepMinutes: 1,
        newStepSeconds: 0,

        init() {
            this.loadFromLocalStorage()

            this.$watch('steps', () => {
                this.saveToLocalStorage()
            })

            this.$watch('metronomeMinutes', value => {
                this.scrollPickerToValue('minutes', value)
            })

            this.$watch('metronomeSeconds', value => {
                this.scrollPickerToValue('seconds', value)
            })

            this.$nextTick(() => {
                this.scrollPickers()
            })
        },

        get currentStep() {
            return this.steps[this.currentIndex]
        },

        get activeDuration() {
            if (this.isPlaying && this.remaining !== null) {
                return this.remaining
            }

            return this.metronome.duration_seconds
        },

        get metronomeMinutes() {
            return Math.floor(this.activeDuration / 60)
        },

        set metronomeMinutes(value) {
            const seconds = this.metronome.duration_seconds % 60
            this.metronome.duration_seconds = (Number(value) * 60) + seconds
            this.clampMetronomeDuration()
            this.remaining = null
            this.scrollPickers()
        },

        get metronomeSeconds() {
            return this.activeDuration % 60
        },

        set metronomeSeconds(value) {
            const minutes = Math.floor(this.metronome.duration_seconds / 60)
            
            this.metronome.duration_seconds = (minutes * 60) + Number(value)
            this.clampMetronomeDuration()
            
            this.remaining = null
            this.scrollPickers()
        },

        toggle() {
            this.isPlaying ? this.stop() : this.startMetronomeSession()
        },

        startMetronomeSession() {
            this.audioContext ??= new AudioContext()
            this.isPlaying = true

            this.startMetronome(this.metronome.bpm)

            if (this.metronome.mode === 'timer') {
                this.startTimer(this.metronome.duration_seconds)
            }
        },

        restartMetronomeSession() {
            const wasPlaying = this.isPlaying

            this.stop()

            if (wasPlaying) {
                this.startMetronomeSession()
            }
        },

        startMetronome(bpm) {
            clearInterval(this.intervalId)
            
            this.currentBeat = 1
            this.tick(true)

            this.intervalId = setInterval(() => {
                this.currentBeat++

                if (this.currentBeat > this.beatsPerMeasure) {
                    this.currentBeat = 1
                }

                this.tick(this.currentBeat === 1)
            }, 60000 / bpm)
        },

        startTimer(duration) {
            clearInterval(this.timerId)

            this.remaining = duration
            this.scrollPickers()

            this.timerId = setInterval(() => {
                this.remaining--
                this.scrollPickers()

                if (this.remaining <= 0) {
                    this.finishMetronomeSession()
                }
            }, 1000)
        },

        stop() {
            clearInterval(this.intervalId)
            clearInterval(this.timerId)

            this.intervalId = null
            this.timerId = null
            this.isPlaying = false
            this.remaining = null
            this.activeExerciseIndex = null

            this.scrollPickers()
        },

        finishMetronomeSession() {
            this.stop()
            this.playFinishSound()
        },

        tick(isAccent = false) {
            const osc = this.audioContext.createOscillator()
            const gain = this.audioContext.createGain()

            osc.frequency.value = isAccent ? 1300 : 900

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

        clampMetronomeDuration() {
            if (this.metronome.duration_seconds > 300) {
                this.metronome.duration_seconds = 300
            }

            if (this.metronome.duration_seconds < 1) {
                this.metronome.duration_seconds = 1
            }
        },

        scrollPickers() {
            this.scrollPickerToValue('minutes', this.metronomeMinutes)
            this.scrollPickerToValue('seconds', this.metronomeSeconds)
        },

        scrollPickerToValue(type, value) {
            this.$nextTick(() => {
                const picker = type === 'minutes'
                    ? this.$refs.minutesPicker
                    : this.$refs.secondsPicker

                if (!picker) return

                const option = picker.querySelector(`[data-value="${value}"]`)

                if (!option) return

                option.scrollIntoView({
                    block: 'center',
                    behavior: 'smooth',
                })
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

        syncPickerFromScroll(type) {
            if (this.isPlaying) return

            const picker = type === 'minutes'
                ? this.$refs.minutesPicker
                : this.$refs.secondsPicker

            if (!picker) return

            const pickerRect = picker.getBoundingClientRect()
            const pickerCenter = pickerRect.top + (pickerRect.height / 2)

            const options = [...picker.querySelectorAll('.picker-option')]

            const closest = options.reduce((selected, option) => {
                const optionRect = option.getBoundingClientRect()
                const optionCenter = optionRect.top + (optionRect.height / 2)

                const selectedRect = selected.getBoundingClientRect()
                const selectedCenter = selectedRect.top + (selectedRect.height / 2)

                return Math.abs(optionCenter - pickerCenter) < Math.abs(selectedCenter - pickerCenter)
                    ? option
                    : selected
            })

            const value = Number(closest.dataset.value)

            if (type === 'minutes') {
                this.metronomeMinutes = value
            }

            if (type === 'seconds') {
                this.metronomeSeconds = value
            }
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

            this.audioContext ??= new AudioContext()
            this.isPlaying = true

            this.startMetronome(this.metronome.bpm)

            if (this.metronome.mode === 'timer' && this.metronome.duration_seconds) {
                this.startTimer(this.metronome.duration_seconds)
            }
        }
    }
}