// Temporary iOS lifecycle diagnostics. Remove after investigation.
const diagnosticKey = 'dorelog:audio-lifecycle-debug'
const documentId = Math.random().toString(36).slice(2) + '-' + Date.now().toString(36)

export function logAudioLifecycle(event, details = {}) {
    const navigation = performance.getEntriesByType('navigation')[0]
    const entry = {
        documentId,
        at: new Date().toISOString(),
        elapsedMs: Math.round(performance.now()),
        event,
        visibility: document.visibilityState,
        navigationType: navigation?.type ?? 'unknown',
        ...details,
    }

    try {
        const stored = JSON.parse(localStorage.getItem(diagnosticKey) || '[]')
        const records = Array.isArray(stored) ? stored : []
        localStorage.setItem(diagnosticKey, JSON.stringify([...records, entry].slice(-40)))
    } catch {
        // Diagnostics must not affect metronome functionality.
    }

    console.debug('[Dorelog lifecycle]', entry)
}

export function audioEngine() {
    return {
        audioContext: null,
        intervalId: null,
        metronomeRunId: 0,
        audioDiagnosticClickCount: 0,
        audioRecoveryGeneration: 0,
        audioRecoverySuspendTimer: null,
        audioRecoveryResumeTimer: null,
        audioRecoverySuspendPromise: null,

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
            if (this.audioContext) return
            const ctx = new AudioContext()
            this.audioContext = ctx
            logAudioLifecycle('audio-created', { state: ctx.state })
            ctx.addEventListener('statechange', () => {
                logAudioLifecycle('audio-statechange', { state: ctx.state })
            })
        },

        // Experimental iOS recovery: only when returning to a stopped metronome.
        // Keep track of an in-flight suspend so START can finish with resume.
        cancelAudioRecovery(reason = 'unspecified') {
            const hadTimers = this.audioRecoverySuspendTimer !== null ||
                this.audioRecoveryResumeTimer !== null
            if (hadTimers) logAudioLifecycle('recovery-cancelled', {
                reason, generation: this.audioRecoveryGeneration,
            })
            ++this.audioRecoveryGeneration
            clearTimeout(this.audioRecoverySuspendTimer)
            clearTimeout(this.audioRecoveryResumeTimer)
            this.audioRecoverySuspendTimer = null
            this.audioRecoveryResumeTimer = null
        },

        resumeAudioForStart() {
            const ctx = this.audioContext
            if (!ctx || ctx.state === 'closed') return

            // Execute synchronously in the START gesture when possible.
            const resume = () => {
                if (this.audioContext !== ctx || ctx.state === 'closed') return
                logAudioLifecycle('start-resume-requested', { state: ctx.state })
                ctx.resume().then(
                    () => logAudioLifecycle('start-resume-completed', { state: ctx.state }),
                    error => logAudioLifecycle('start-resume-rejected', { error: String(error) })
                )
            }

            if (ctx.state !== 'running') resume()

            // A previously requested suspend may settle *after* START.
            // Ensure its completion cannot leave the new session suspended.
            const pendingSuspend = this.audioRecoverySuspendPromise
            if (pendingSuspend) {
                pendingSuspend.then(() => {
                    if (this.audioContext === ctx && this.isPlaying &&
                        ctx.state !== 'closed') resume()
                }, () => {})
            }
        },

        scheduleAudioRecovery() {
            this.cancelAudioRecovery('rescheduled')
            const ctx = this.audioContext
            if (!ctx || ctx.state === 'closed' || this.isPlaying) {
                logAudioLifecycle('recovery-skipped', {
                    reason: !ctx ? 'no-context' : ctx.state === 'closed' ? 'closed' : 'playing',
                    state: ctx?.state ?? null,
                })
                return
            }

            const generation = this.audioRecoveryGeneration
            logAudioLifecycle('recovery-scheduled', { generation, state: ctx.state })
            this.audioRecoverySuspendTimer = setTimeout(() => {
                this.audioRecoverySuspendTimer = null
                if (generation !== this.audioRecoveryGeneration ||
                    document.hidden || this.isPlaying || this.audioContext !== ctx) return

                logAudioLifecycle('suspend-requested', { generation, state: ctx.state })
                const pending = ctx.suspend()
                this.audioRecoverySuspendPromise = pending
                pending.then(
                    () => logAudioLifecycle('suspend-completed', { generation, state: ctx.state }),
                    error => logAudioLifecycle('suspend-rejected', {
                        generation, error: String(error),
                    })
                ).finally(() => {
                    if (this.audioRecoverySuspendPromise === pending) {
                        this.audioRecoverySuspendPromise = null
                    }
                })

                // Resume only after suspend completes; do not race independent timers.
                pending.then(() => {
                    if (generation !== this.audioRecoveryGeneration ||
                        document.hidden || this.isPlaying || this.audioContext !== ctx) return
                    this.audioRecoveryResumeTimer = setTimeout(() => {
                        this.audioRecoveryResumeTimer = null
                        if (generation !== this.audioRecoveryGeneration ||
                            document.hidden || this.isPlaying || this.audioContext !== ctx) return
                        logAudioLifecycle('resume-requested', { generation, state: ctx.state })
                        ctx.resume().then(
                            () => logAudioLifecycle('resume-completed', { generation, state: ctx.state }),
                            error => logAudioLifecycle('resume-rejected', {
                                generation, error: String(error),
                            })
                        )
                    }, 250)
                }, () => {})
            }, 250)
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

            // Sparse diagnostics only: scheduling a source does not prove audibility.
            if (this.isPlaying) {
                const count = ++this.audioDiagnosticClickCount
                if ([1, 2, 5, 10, 20, 40, 80, 160].includes(count)) {
                    logAudioLifecycle('click-source-started', {
                        runId: this.metronomeRunId,
                        count,
                        beat: this.currentBeat,
                        state: this.audioContext.state,
                        audioTime: this.audioContext.currentTime,
                        bufferDuration: buffer?.duration ?? null,
                    })
                }
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
            this.audioDiagnosticClickCount = 0
            logAudioLifecycle('metronome-start-requested', {
                runId, bpm, state: this.audioContext?.state ?? null,
                audioTime: this.audioContext?.currentTime ?? null,
                buffersReady: Boolean(this.clickBuffer && this.accentBuffer),
            })

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
                logAudioLifecycle('metronome-start-stale', { runId })
                return
            }

            logAudioLifecycle('metronome-started', {
                runId, bpm, state: this.audioContext?.state ?? null,
                audioTime: this.audioContext?.currentTime ?? null,
                buffersReady: Boolean(this.clickBuffer && this.accentBuffer),
            })

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
