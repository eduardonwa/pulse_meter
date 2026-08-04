export function audioEngine() {
    return {
        audioContext: null,
        intervalId: null,

        activeMetronomeBpm: null,
        currentBeat: 1,

        clickBuffer: null,
        accentBuffer: null,
        finishBuffer: null,

        defaultPlaybackPulse: {
            timeSignature: {
                numerator: 4,
                denominator: 4
            },

            grouping: [4],

            pattern: [
                { sound: 'accent', groupStart: true },
                { sound: 'click', groupStart: false },
                { sound: 'click', groupStart: false },
                { sound: 'click', groupStart: false },
            ]
        },

        // AUDIO CONTEXT
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

        // SOUND LOADING
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

        // BUFFER PLAYBACK
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

        tick(isAccent = false) {
            const buffer = isAccent ? this.accentBuffer : this.clickBuffer

            if (!buffer) {
                return
            }

            this.playBuffer(buffer, isAccent ? 1 : 0.9)
        },

        // START METRONOME
        async startMetronome(bpm) {
            clearInterval(this.intervalId)

            await this.loadClickSounds()

            const playbackPulse = this.getPlaybackPulse()

            this.activeMetronomeBpm = bpm
            this.currentBeat = 1
            
            this.playPatternBeat()

            this.intervalId = setInterval(() => {
                const playbackPulse = this.getPlaybackPulse()
                const numerator = playbackPulse.timeSignature.numerator

                this.currentBeat++

                if (this.currentBeat > numerator) {
                    this.currentBeat = 1
                }

                this.playPatternBeat()
            }, this.getBeatIntervalMs(bpm))
        },

        getBeatIntervalMs(bpm) {
            const playbackPulse = this.getPlaybackPulse()
            const denominator = playbackPulse.timeSignature.denominator

            return (60000 / bpm) * (4 / denominator)
        },

        playPatternBeat(beat = this.currentBeat) {
            const playbackPulse = this.getPlaybackPulse()
            const patternBeat = playbackPulse.pattern[beat - 1]

            if (!patternBeat) { return }

            /*
            * PULSE MODE
            *
            * Solo suena el inicio de cada grupo.
            */
            if (
                this.metronome.mode === 'creative'
                && this.creativePlaybackMode === 'pulse'
            ) {
                if (!patternBeat.groupStart) {
                    return
                }

                if (patternBeat.sound === 'rest') {
                    return
                }

                this.playPulseTone({
                    groupSize:
                        this.getGroupSizeForBeat(beat),

                    isDownbeat:
                        this.pulseDownbeatEnabled
                        && beat === 1,
                })

                return
            }

            if (patternBeat.sound === 'rest') {
                return
            }

            this.tick(
                patternBeat.sound === 'accent'
            )
        },

        // PULSE PLAYBACK
        playPulseTone({
            groupSize,
            isDownbeat = false,
        }) {
            if (!groupSize) {
                return
            }

            this.ensureAudioContext()

            const ctx = this.audioContext
            const now = ctx.currentTime

            const bpm =
                this.activeMetronomeBpm
                ?? this.metronome.bpm

            const subdivisionSeconds =
                this.getBeatIntervalMs(bpm) / 1000

            const groupDuration =
                groupSize * subdivisionSeconds

            const decayDuration =
                Math.max(
                    0.08,
                    groupDuration * 0.88
                )

            const oscillator =
                ctx.createOscillator()

            const gain =
                ctx.createGain()

            /*
            * DOWNBEAT:
            *
            * true  = BUM
            * false = TUM
            */
            oscillator.type =
                isDownbeat
                    ? 'sine'
                    : 'triangle'

            const startFrequency =
                isDownbeat
                    ? 135
                    : 190

            const endFrequency =
                isDownbeat
                    ? 75
                    : 105

            const volume =
                isDownbeat
                    ? 0.65
                    : 0.45

            oscillator.frequency.setValueAtTime(
                startFrequency,
                now
            )

            oscillator.frequency.exponentialRampToValueAtTime(
                endFrequency,
                now + Math.min(
                    0.14,
                    decayDuration * 0.35
                )
            )

            gain.gain.setValueAtTime(
                volume,
                now
            )

            gain.gain.exponentialRampToValueAtTime(
                0.001,
                now + decayDuration
            )

            oscillator.connect(gain)
            gain.connect(ctx.destination)

            oscillator.start(now)

            oscillator.stop(
                now + decayDuration + 0.02
            )

            oscillator.onended = () => {
                oscillator.disconnect()
                gain.disconnect()
            }
        },

        getPlaybackPulse() {
            if (this.metronome.mode === 'creative') {
                return {
                    timeSignature: this.timeSignature,
                    grouping: this.grouping,
                    pattern: this.pattern,
                }
            }

            return this.defaultPlaybackPulse
        },

        // PLAYBACK CONTROLS
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

        getPlaybackBeatCount() {
            return this
                .getPlaybackPulse()
                .timeSignature
                .numerator
        },

        // FINISH SOUND
        async playFinishSound() {
            await this.loadClickSounds()

            this.playBuffer(this.finishBuffer, 1)
        },
    }
}