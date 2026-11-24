export function audioEngine() {
    return {
        audioContext: null,
        intervalId: null,
        metronomeRunId: 0,

        activeMetronomeBpm: null,
        currentBeat: 1,
        currentSubdivision: 0,

        clickBuffer: null,
        accentBuffer: null,
        finishBuffer: null,

        soundLoadingPromise: null,
        soundLoadingProfile: null,

        defaultPlaybackPulse: {
            timeSignature: {
                numerator: 4,
                denominator: 4
            },
            
            subdivision: 1,

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

            if (this.soundLoadingPromise && this.soundLoadingProfile === profile) {
                return this.soundLoadingPromise
            }

            const paths = [
                profile.click,
                profile.accent,
                profile.finish,
            ]

            const uniquePaths = [...new Set(paths)]

            const loading = (async () => {
                const buffers = await Promise.all(
                    uniquePaths.map(path => this.loadAudioBuffer(path))
                )

                const buffersByPath = new Map(
                    uniquePaths.map((path, index) => [path, buffers[index]])
                )

                if (
                    this.currentDawProfile !== profile ||
                    this.soundLoadingPromise !== loading
                ) {
                    return
                }

                this.clickBuffer = buffersByPath.get(profile.click)
                this.accentBuffer = buffersByPath.get(profile.accent)
                this.finishBuffer = buffersByPath.get(profile.finish)
            })()

            this.soundLoadingPromise = loading
            this.soundLoadingProfile = profile

            try {
                await loading
            } finally {
                if (this.soundLoadingPromise === loading) {
                    this.soundLoadingPromise = null
                    this.soundLoadingProfile = null
                }
            }
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
            /*
            * Cada Start invalida cualquier inicio anterior
            * que todavía esté esperando la carga de sonidos.
            */
            const runId = ++this.metronomeRunId

            clearInterval(this.intervalId)
            this.intervalId = null

            await this.loadClickSounds()

            /*
            * Durante la espera pudo ocurrir:
            *
            * - Stop
            * - otro Start
            * - cambio de BPM
            *
            * En esos casos, esta ejecución ya es antigua
            * y no debe comenzar a sonar.
            */
            if (runId !== this.metronomeRunId) {
                return
            }

            this.activeMetronomeBpm = bpm
            this.currentBeat = 1
            this.currentSubdivision = 0

            this.playPatternBeat()

            this.intervalId = setInterval(() => {
                this.advancePatternPosition()
                this.playPatternPosition()
            }, this.getSubdivisionIntervalMs(bpm))
        },

        getBeatIntervalMs(bpm) {
            const playbackPulse = this.getPlaybackPulse()
            const denominator = playbackPulse.timeSignature.denominator

            return (60000 / bpm) * (4 / denominator)
        },

        getSubdivisionIntervalMs(bpm) {
            const playbackPulse = this.getPlaybackPulse()

            const subdivision =
                Number(playbackPulse.subdivision) || 1

            return this.getBeatIntervalMs(bpm) / subdivision
        },

        advancePatternPosition() {
            const playbackPulse = this.getPlaybackPulse()

            const subdivision =
                Number(playbackPulse.subdivision) || 1

            const numerator =
                playbackPulse.timeSignature.numerator

            this.currentSubdivision++

            if (this.currentSubdivision >= subdivision) {
                this.currentSubdivision = 0
                this.currentBeat++

                if (this.currentBeat > numerator) {
                    this.currentBeat = 1
                }
            }
        },

        playPatternBeat(beat = this.currentBeat) {
            const playbackPulse = this.getPlaybackPulse()
            const patternBeat = playbackPulse.pattern[beat - 1]

            if (!patternBeat || patternBeat.sound === 'rest') return

            if (
                this.metronome.mode === 'creative'
                && this.getCreativePlaybackMode() === 'pulse'
            ) {
                this.playPulseTone({
                    isAccent: patternBeat.sound === 'accent'
                        || Boolean(patternBeat.groupStart),
                    isDownbeat: this.pulseDownbeatEnabled && beat === 1,
                })
                return
            }

            this.tick(patternBeat.sound === 'accent')
        },

        playPatternPosition() {
            if (this.currentSubdivision === 0) {
                this.playPatternBeat(this.currentBeat)
                return
            }

            const playbackPulse = this.getPlaybackPulse()
            const patternBeat = playbackPulse.pattern[this.currentBeat - 1]
            const subdivisionItem = patternBeat?.subdivisions?.[
                this.currentSubdivision - 1
            ]

            if (!subdivisionItem || subdivisionItem.sound === 'rest') return

            if (
                this.metronome.mode === 'creative'
                && this.getCreativePlaybackMode() === 'pulse'
            ) {
                this.playPulseTone({
                    isAccent: subdivisionItem.sound === 'accent'
                        || Boolean(subdivisionItem.groupStart),
                })
                return
            }

            this.tick(subdivisionItem.sound === 'accent')
        },

        getPlaybackGroupSizeForBeat(
            beat,
            playbackPulse = this.getPlaybackPulse(),
            subdivisionPosition = 0
        ) {
            let groupIndex = -1

            playbackPulse.pattern.forEach((item, index) => {
                if (index + 1 > beat) return

                if (item.groupStart) groupIndex++

                item.subdivisions?.forEach((sub, subIndex) => {
                    if (
                        sub.groupStart
                        && (index + 1 < beat || subIndex + 1 <= subdivisionPosition)
                    ) {
                        groupIndex++
                    }
                })
            })

            return playbackPulse.grouping[groupIndex] ?? 0
        },

        // Pulse uses synthesized tones for every audible pattern position.
        // Keep the organic pitch sweep while shortening each envelope to avoid
        // overlap at eighth- and sixteenth-note subdivisions.
        playPulseTone({
            isAccent = false,
            isDownbeat = false,
        } = {}) {
            this.ensureAudioContext()

            const ctx = this.audioContext
            const now = ctx.currentTime
            const bpm = this.activeMetronomeBpm ?? this.metronome.bpm
            const stepSeconds = this.getSubdivisionIntervalMs(bpm) / 1000
            const decayDuration = Math.max(
                0.025,
                Math.min(0.22, stepSeconds * 0.72)
            )

            const oscillator = ctx.createOscillator()
            const gain = ctx.createGain()

            oscillator.type = isDownbeat ? 'sine' : 'triangle'

            const startFrequency = isDownbeat
                ? 135
                : isAccent ? 190 : 205
            const endFrequency = isDownbeat
                ? 75
                : isAccent ? 105 : 145
            const volume = isDownbeat
                ? 0.65
                : isAccent ? 0.45 : 0.23

            oscillator.frequency.setValueAtTime(startFrequency, now)
            oscillator.frequency.exponentialRampToValueAtTime(
                endFrequency,
                now + Math.min(0.10, decayDuration * 0.55)
            )

            gain.gain.setValueAtTime(0.001, now)
            gain.gain.linearRampToValueAtTime(
                volume,
                now + Math.min(0.008, decayDuration * 0.12)
            )
            gain.gain.exponentialRampToValueAtTime(
                0.001,
                now + decayDuration
            )

            oscillator.connect(gain)
            gain.connect(ctx.destination)

            oscillator.start(now)
            oscillator.stop(now + decayDuration + 0.01)

            oscillator.onended = () => {
                oscillator.disconnect()
                gain.disconnect()
            }
        },

        getPlaybackPulse() {
            if (this.metronome.mode === 'creative') {
                if (
                    this.creativeMode === 'randomizer'
                    && this.randomizerPulse
                ) {
                    return this.randomizerPulse
                }

                return {
                    timeSignature: this.timeSignature,
                    subdivision: this.subdivision,
                    grouping: this.grouping,
                    pattern: this.pattern,
                }
            }

            return this.getStandardPlaybackPulse()
        },

        getCreativePlaybackMode() {
            if (this.creativeMode === 'randomizer') {
                return this.randomizerPlaybackMode
                    ?? 'click'
            }

            return this.creativePlaybackMode
                ?? 'click'
        },

        // PLAYBACK CONTROLS
        stopMetronome() {
            /*
            * Invalida también cualquier Start que siga
            * esperando la carga asíncrona de sonidos.
            */
            this.metronomeRunId += 1

            clearInterval(this.intervalId)

            this.intervalId = null
            this.currentBeat = 1
            this.currentSubdivision = 0
        },

        getPlaybackBeatCount() {
            return this
                .getPlaybackPulse()
                .timeSignature
                .numerator
        },

        getStandardPlaybackPulse() {
            const requestedNumerator =
                Number(
                    this.metronome
                        .time_signature_numerator
                )

            const numerator =
                Number.isInteger(requestedNumerator)
                && requestedNumerator >= 1
                && requestedNumerator <= 16
                    ? requestedNumerator
                    : 4

            const requestedDenominator = Number(this.metronome.time_signature_denominator)
            const denominator = [2, 4, 8, 16].includes(requestedDenominator) ? requestedDenominator : 4

            return {
                timeSignature: {
                    numerator,
                    denominator,
                },

                subdivision: 1,
                grouping: [numerator],

                pattern: Array.from(
                    { length: numerator },
                    (_, index) => ({
                        sound:
                            index === 0
                                ? 'accent'
                                : 'click',

                        groupStart:
                            index === 0,
                    })
                ),
            }
        },

        // FINISH SOUND
        async playFinishSound() {
            await this.loadClickSounds()

            this.playBuffer(this.finishBuffer, 1)
        },
    }
}
