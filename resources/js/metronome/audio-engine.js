export function audioEngine() {
    return {
        ensureAudioContext() {
            this.audioContext ??= new AudioContext()
        },

        async loadAudioBuffer(path) {
            this.ensureAudioContext()

            const response = await fetch(path)

            if (!response.ok) {
                throw new Error(`Could not load audio file: ${path}`)
            }

            const arrayBuffer = await response.arrayBuffer()

            return await this.audioContext.decodeAudioData(arrayBuffer)
        },

        async loadClickSounds() {
            if (this.clickBuffer && this.accentBuffer && this.finishBuffer) {
                return
            }

            const profile = this.currentDawProfile

            if (!profile?.click || !profile?.accent || !profile?.finish) {
                console.warn('Missing click profile audio paths', profile)
                return
            }

            this.clickBuffer = await this.loadAudioBuffer(profile.click)
            this.accentBuffer = await this.loadAudioBuffer(profile.accent)
            this.finishBuffer = await this.loadAudioBuffer(profile.finish)
        },

        playBuffer(buffer, volume = 1) {
            this.ensureAudioContext()

            const now = this.audioContext.currentTime
            const source = this.audioContext.createBufferSource()
            const gain = this.audioContext.createGain()

            source.buffer = buffer

            gain.gain.setValueAtTime(volume, now)

            source.connect(gain)
            gain.connect(this.audioContext.destination)

            source.start(now)

            source.onended = () => {
                source.disconnect()
                gain.disconnect()
            }
        },

        async startMetronome(bpm) {
            clearInterval(this.intervalId)

            await this.loadClickSounds()

            this.currentBeat = 1
            this.playPatternBeat()

            this.intervalId = setInterval(() => {
                this.currentBeat++

                if (this.currentBeat > this.timeSignature.numerator) {
                    this.currentBeat = 1
                }

                this.playPatternBeat()
            }, this.getBeatIntervalMs(bpm))
        },

        getBeatIntervalMs(bpm) {
            const denominator = this.timeSignature.denominator

            return (60000 / bpm) * (4 / denominator)
        },

        tick(isAccent = false) {
            const buffer = isAccent ? this.accentBuffer : this.clickBuffer

            if (!buffer) {
                return
            }

            this.playBuffer(buffer, isAccent ? 1 : 0.9)
        },

        playPatternBeat(beat = this.currentBeat) {
            const patternBeat = this.pattern[beat - 1]

            if (!patternBeat || patternBeat.sound === 'rest') {
                return
            }

            this.tick(patternBeat.sound === 'accent')
        },

        // END ACTIVITIES
        async playFinishSound() {
            await this.loadClickSounds()

            this.playBuffer(this.finishBuffer, 1)
        },

        stopMetronome() {
            clearInterval(this.intervalId)

            this.intervalId = null
            this.currentBeat = 1
        },

        stop(reason = 'user') {
            const wasPlaying = this.isPlaying

            if (wasPlaying) {
                this.endPlaybackTracking(reason)
            }

            this.stopMetronome()

            clearInterval(this.timerId)

            this.timerId = null
            this.isPlaying = false
            this.remaining = null
            this.activeExerciseIndex = null
        },
    }
}