export function audioEngine() {
    return {
        audioContext: null,
        intervalId: null,
        metronomeRunId: 0,
        audioEvents: [],
        audioStateListener: null,
        audioRecoverySuspendTimer: null,
        audioRecoveryResumeTimer: null,
        audioRecoveryGeneration: 0,
        audioStartPerfTime: null,
        audioStartRunId: null,

        activeMetronomeBpm: null,
        currentBeat: 1,
        currentSubdivision: 0,

        clickBuffer: null,
        accentBuffer: null,
        finishBuffer: null,

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
            if (this.audioContext && this.audioContext.state !== 'closed') return

            this.audioContext = new AudioContext()
            this.audioContext.onstatechange = () => {
                this.recordAudioEvent('statechange')
            }
            this.recordAudioEvent('context-created')
        },

        recordAudioEvent(event, details = {}) {
            const entry = {
                event,
                state: this.audioContext?.state ?? 'missing',
                visibility: document.visibilityState,
                clickLoaded: !!this.clickBuffer,
                accentLoaded: !!this.accentBuffer,
                time: new Date().toISOString(),
                ...details,
            }
            this.audioEvents.push(entry)
            if (this.audioEvents.length > 40) this.audioEvents.shift()
            window.__dorelogAudioDiagnostics = this.audioEvents
            console.info('[Dorelog audio]', entry)
        },

        timingDetails() {
            const ctx = this.audioContext
            const elapsed = this.audioStartPerfTime === null
                ? null : Number((performance.now() - this.audioStartPerfTime).toFixed(2))
            let outputTimestamp = null
            try {
                const stamp = ctx?.getOutputTimestamp?.()
                if (stamp && stamp.performanceTime > 0) {
                    outputTimestamp = {
                        contextTime: Number(stamp.contextTime.toFixed(4)),
                        performanceTime: Number(stamp.performanceTime.toFixed(2)),
                    }
                }
            } catch (_) {
                // Optional browser API.
            }
            return {
                msFromStart: elapsed,
                audioTime: ctx ? Number(ctx.currentTime.toFixed(4)) : null,
                baseLatencyMs: ctx?.baseLatency == null ? null : Number((ctx.baseLatency * 1000).toFixed(2)),
                outputLatencyMs: ctx?.outputLatency == null ? null : Number((ctx.outputLatency * 1000).toFixed(2)),
                outputTimestamp,
            }
        },

        // Called synchronously from the actual START gesture so Firefox/iOS
        // can resume audio without waiting for a file fetch or decode.
        wakeAudioContext() {
            this.cancelAudioRecovery()
            this.ensureAudioContext()
            this.recordAudioEvent('start-gesture')
            if (this.audioContext.state !== 'running') {
                this.audioContext.resume()
                    .then(() => this.recordAudioEvent('resume-resolved'))
                    .catch(error => this.recordAudioEvent('resume-failed', {
                        error: String(error),
                    }))
            }
        },

        cancelAudioRecovery() {
            ++this.audioRecoveryGeneration
            clearTimeout(this.audioRecoverySuspendTimer)
            clearTimeout(this.audioRecoveryResumeTimer)
            this.audioRecoverySuspendTimer = null
            this.audioRecoveryResumeTimer = null
        },

        // Experimental WebKit recovery on returning to this tab.
        // Never alter an active metronome session.
        scheduleAudioRecovery() {
            this.cancelAudioRecovery()
            const ctx = this.audioContext
            if (!ctx || ctx.state === 'closed' || this.isPlaying) {
                this.recordAudioEvent('recovery-skipped', {
                    reason: !ctx ? 'no-context' : this.isPlaying ? 'playing' : 'closed',
                })
                return
            }

            const generation = this.audioRecoveryGeneration
            this.recordAudioEvent('recovery-scheduled')
            this.audioRecoverySuspendTimer = setTimeout(() => {
                if (generation !== this.audioRecoveryGeneration ||
                    document.hidden || this.isPlaying || this.audioContext !== ctx) {
                    this.recordAudioEvent('recovery-cancelled')
                    return
                }
                this.recordAudioEvent('recovery-suspend-requested')
                ctx.suspend()
                    .then(() => this.recordAudioEvent('recovery-suspended'))
                    .catch(error => this.recordAudioEvent('recovery-suspend-failed', {
                        error: String(error),
                    }))
            }, 250)

            this.audioRecoveryResumeTimer = setTimeout(() => {
                if (generation !== this.audioRecoveryGeneration ||
                    document.hidden || this.isPlaying || this.audioContext !== ctx) {
                    this.recordAudioEvent('recovery-cancelled')
                    return
                }
                this.recordAudioEvent('recovery-resume-requested')
                ctx.resume()
                    .then(() => this.recordAudioEvent('recovery-resumed'))
                    .catch(error => this.recordAudioEvent('recovery-resume-failed', {
                        error: String(error),
                    }))
            }, 500)
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
            if (this.audioStartRunId === this.metronomeRunId) {
                this.recordAudioEvent('audio-buffer-source-start', this.timingDetails())
                this.audioStartRunId = null
            }

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

            this.recordAudioEvent('metronome-loading')
            try {
                await this.loadClickSounds()
            } catch (error) {
                this.recordAudioEvent('samples-failed', { error: String(error) })
                if (runId === this.metronomeRunId) this.stop('audio_error')
                return
            }
            this.recordAudioEvent('samples-ready')

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
            this.audioStartRunId = runId
            this.currentBeat = 1
            this.currentSubdivision = 0

            this.recordAudioEvent('first-beat-scheduled', this.timingDetails())
            this.playPatternBeat()
            // The UI updates reactively, so the next frame is an approximation
            // of visual presentation, not proof of pixel/physical speaker timing.
            requestAnimationFrame(() => {
                if (runId === this.metronomeRunId) {
                    this.recordAudioEvent('visual-frame-after-start', this.timingDetails())
                }
            })

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

            if (!patternBeat) { return }

            /*
            * PULSE MODE
            *
            * Solo suena el inicio de cada grupo.
            */
            if (
                this.metronome.mode === 'creative'
                && this.getCreativePlaybackMode() === 'pulse'
            ) {
                if (!patternBeat.groupStart) {
                    return
                }

                if (patternBeat.sound === 'rest') {
                    return
                }

                this.playPulseTone({
                    groupSize:
                        this.getPlaybackGroupSizeForBeat(
                            beat,
                            playbackPulse
                        ),

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

        playPatternPosition() {
            // Beat principal: 1, 2, 3, 4...
            if (this.currentSubdivision === 0) {
                this.playPatternBeat(this.currentBeat)
                return
            }

            // En Pulse mode las subdivisiones no generan
            // los tonos de agrupación.
            if (
                this.metronome.mode === 'creative'
                && this.getCreativePlaybackMode() === 'pulse'
            ) {
                return
            }

            const playbackPulse = this.getPlaybackPulse()

            const patternBeat =
                playbackPulse.pattern[this.currentBeat - 1]

            const subdivisionItem =
                patternBeat?.subdivisions?.[
                    this.currentSubdivision - 1
                ]

            if (!subdivisionItem) {
                return
            }

            if (subdivisionItem.sound === 'rest') {
                return
            }

            this.tick(
                subdivisionItem.sound === 'accent'
            )
        },

        getPlaybackGroupSizeForBeat(
            beat,
            playbackPulse = this.getPlaybackPulse()
        ) {
            let groupIndex = -1

            for (let i = 0; i < beat; i++) {
                if (
                    playbackPulse
                        .pattern[i]
                        ?.groupStart
                ) {
                    groupIndex++
                }
            }

            if (groupIndex < 0) {
                return 0
            }

            return playbackPulse
                .grouping[groupIndex]
                ?? 0
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
            if (this.audioStartRunId === this.metronomeRunId) {
                this.recordAudioEvent('audio-oscillator-start', this.timingDetails())
                this.audioStartRunId = null
            }

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
            this.audioStartRunId = null

            clearInterval(this.intervalId)

            this.intervalId = null
            this.recordAudioEvent('metronome-stopped')
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
                && requestedNumerator >= 2
                && requestedNumerator <= 16
                    ? requestedNumerator
                    : 4

            const denominator = 4

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
