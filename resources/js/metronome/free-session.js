export function freeSession() {
    return {
        toggle() {
            this.isPlaying ? this.stop() : this.startMetronomeSession()
        },

        startMetronomeSession() {
            this.ensureAudioContext()

            this.activeExerciseIndex = null
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

        startTimer(duration) {
            clearInterval(this.timerId)

            this.remaining = duration

            this.timerId = setInterval(() => {
                this.remaining--

                if (this.remaining <= 0) {
                    this.finishMetronomeSession()
                }
            }, 1000)
        },

        finishMetronomeSession() {
            this.stop()
            this.playFinishSound()
        },
    }
}