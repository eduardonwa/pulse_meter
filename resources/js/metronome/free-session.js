export function freeSession() {
    return {
        toggle() {
            this.isPlaying ? this.stop() : this.startMetronomeSession()
        },

        startMetronomeSession() {
            const mode = this.metronome.mode

            const configuredDuration =
                mode === 'timer'
                    ? Number(this.metronome.duration_seconds)
                    : null

            this.ensureAudioContext()

            this.activeSessionType = 'free'
            this.activeExerciseIndex = null
            this.isPlaying = true

            if (mode !== 'creative') {
                this.saveCurrentSession(mode)
            }

            this.startMetronome(this.metronome.bpm)

            this.beginPlaybackTracking({
                source: 'free_session',
                metronome_mode: mode,
                bpm: Number(this.metronome.bpm),
                configured_duration_seconds:
                    configuredDuration,
            })

            if (mode === 'timer') {
                this.startTimer(configuredDuration)
            }
        },

        restartMetronomeSession() {
            const wasPlaying = this.isPlaying

            this.stop()

            if (wasPlaying) {
                this.startMetronomeSession()
            }
        },

        handleBpmChange() {
            clearTimeout(this.bpmChangeTimeoutId)

            if (!this.isPlaying) {
                return
            }

            this.bpmChangeTimeoutId = setTimeout(() => {
                this.bpmChangeTimeoutId = null
                this.restartMetronome()
                
                this.saveCurrentSession()
            }, 500)
        },

        restartMetronome() {
            this.stopMetronome()
            this.startMetronome(this.metronome.bpm)
        },

        startTimer(duration) {
            clearInterval(this.timerId)

            this.remaining = duration

            this.timerId = setInterval(() => {
                this.remaining--

                if (this.remaining <= 0) {
                    this.finishCurrentTimedSession()
                }
            }, 1000)
        },

        finishCurrentTimedSession() {
            if (this.activeSessionType === 'exercise') {
                this.finishExerciseSession()
                return
            }

            this.finishMetronomeSession()
        },

        finishMetronomeSession() {
            this.stop()
            this.playFinishSound()
        },
    }
}