export function playbackSession() {
    return {
        bpmChangeTimeoutId: null,
        timerId: null,
        remaining: null,
        playbackSource: null,
        
        // SESSION CONTROLS
        toggle() {
            this.isPlaying ? this.stop() : this.startMetronomeSession()
        },

        startPlaybackSession({
            source,
            mode,
            bpm,
            duration = null,
            exerciseIndex = null,
        }) {
            /*
            * Cierra cualquier sesión anterior antes
            * de configurar la nueva.
            */
            this.stop('replaced')
            
            const normalizedBpm = Number(bpm)
            
            const normalizedDuration = mode === 'timer' ? Number(duration ?? 60) : null

            this.ensureAudioContext()

            this.playbackSource = source
            this.activeExerciseIndex = exerciseIndex

            this.metronome.bpm = normalizedBpm
            this.metronome.mode = mode

            /*
            * No borramos la duración configurada al entrar
            * en Classic o Creative.
            */
            if (mode === 'timer') {
                this.metronome.duration_seconds = normalizedDuration
            }

            this.isPlaying = true

            this.startMetronome(normalizedBpm)

            if (mode === 'timer' && normalizedDuration !== null) {
                this.startTimer(normalizedDuration)
            }

            return {
                mode,
                bpm: normalizedBpm,
                duration: normalizedDuration,
            }
        },
        
        startMetronomeSession() {
            const mode = this.metronome.mode

            const configuredDuration = mode === 'timer' ? Number(this.metronome.duration_seconds) : null

            this.startPlaybackSession({
                source: 'free',
                mode,
                bpm: this.metronome.bpm,
                duration: configuredDuration,
                exerciseIndex: null,
            })

            if (mode !== 'creative') {
                this.saveCurrentSession(mode)
            }

            this.beginPlaybackTracking({
                source: 'free_session',
                metronome_mode: mode,
                bpm: Number(this.metronome.bpm),
                configured_duration_seconds:
                    configuredDuration,
            })
        },
            
        restartMetronomeSession() {
            const wasPlaying = this.isPlaying
        
            this.stop()
            
            if (wasPlaying) {
                this.startMetronomeSession()
            }
        },

        stop(reason = 'user') {
            const wasPlaying = this.isPlaying

            if (wasPlaying) {
                this.endPlaybackTracking(reason)
            }

            /*
            * Detiene únicamente el motor de beats.
            */
            this.stopMetronome()

            /*
            * Detiene cualquier cuenta regresiva activa.
            */
            clearInterval(this.timerId)

            this.timerId = null
            this.isPlaying = false
            this.remaining = null
            this.activeExerciseIndex = null
            this.playbackSource = null
        },
        
        // BPM UPDATES
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
        
        // TIMER FLOW
        startTimer(duration) {
            clearInterval(this.timerId)
            
            this.remaining = duration
        
            this.timerId = setInterval(() => {
                this.remaining--
            
            if (this.remaining <= 0) {
                this.finishCurrentTimedSession() }
            }, 1000)
        },
                
        finishCurrentTimedSession() {
            if (this.playbackSource === 'exercise') {
                this.finishExerciseSession()
                return
            }
            
            this.finishMetronomeSession()
        },
            
        // SESSION COMPLETION
        finishMetronomeSession() {
            this.stop()
            this.playFinishSound()
        },

        handleSessionModeChange(mode) {
            if (this.isPlaying) {
                this.stop('mode_changed')
            }

            this.metronome.mode = mode
            this.currentBeat = 1
            /*
            * Los controles del modo anterior podían estar ocultos
            * mediante x-show. Esperamos a que Alpine muestre
            * los controles del nuevo modo y sincronizamos sus pickers.
            */
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    window.dispatchEvent(
                        new Event('picker:sync')
                    )
                })
            })
        },
    }
}