export function audioEngine() {
    return {
        ensureAudioContext() {
            this.audioContext ??= new AudioContext()
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

        tick(isAccent = false) {
            this.ensureAudioContext()

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
            this.ensureAudioContext()

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

        stop() {
            clearInterval(this.intervalId)
            clearInterval(this.timerId)

            this.intervalId = null
            this.timerId = null
            this.isPlaying = false
            this.remaining = null
            this.activeExerciseIndex = null
        },
    }
}