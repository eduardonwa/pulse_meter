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
            const buffer = isAccent ? this.accentBuffer : this.clickBuffer

            if (!buffer) {
                return
            }

            this.playBuffer(buffer, isAccent ? 1 : 0.9)
        },

        async playFinishSound() {
            await this.loadClickSounds()

            this.playBuffer(this.finishBuffer, 1)
        },

        stop() {
            clearInterval(this.intervalId)
            clearInterval(this.timerId)

            this.intervalId = null
            this.timerId = null
            this.isPlaying = false
            this.remaining = null
            this.activeExerciseIndex = null
            this.currentBeat = 1
        },
    }
}