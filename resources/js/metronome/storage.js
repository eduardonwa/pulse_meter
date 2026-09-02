import {
    defaultSteps,
    defaultMetronome,
    MIN_BPM,
    MAX_BPM
} from './state.js'

// DATA MIGRATION
function normalizeMode(mode) {
    return mode === 'manual'
        ? 'classic'
        : mode
}

export function storage() {
    return {
        storageKey: 'pulse_meter_routine',
        showResetAppModal: false,

        pendingLocalRoutineImport: null,
        isLocalRoutineImportOpen: false,
        isLocalRoutineImportBusy: false,
        localRoutineImportError: null,
        localRoutineImportLimitReached: false,
        
        // ROUTINE PERSISTENCE
        saveToLocalStorage() {
            localStorage.setItem(this.storageKey, JSON.stringify(this.steps))
        },

        loadFromLocalStorage() {
            const saved = localStorage.getItem(this.storageKey)

            if (!saved) {
                this.steps = defaultSteps()
                return
            }

            try {
                const parsedSteps = JSON.parse(saved)

                const normalizedSteps = Array.isArray(parsedSteps)
                        ? parsedSteps.map(step => ({
                            ...step,

                            mode: normalizeMode(step.mode),
                            
                            time_signature_numerator:
                                    [2, 3, 4].includes(
                                        Number(
                                            step.time_signature_numerator
                                        )
                                    )
                                        ? Number(
                                            step.time_signature_numerator
                                        )
                                        : 4,

                                time_signature_denominator: 4,
                        }))
                        : []

                this.steps = normalizedSteps.length
                    ? normalizedSteps
                    : defaultSteps()

                // Guarda nuevamente los datos ya migrados.
                if (
                    Array.isArray(parsedSteps)
                    && parsedSteps.some(step => step.mode === 'manual')
                ) {
                    this.saveToLocalStorage()
                }
            } catch (error) {
                this.steps = defaultSteps()
            }
        },

        getLocalRoutineImportConfig() {
            const importUrl = this.$root?.dataset?.localRoutineImportUrl
            const markerKey = this.$root?.dataset?.localRoutineImportMarkerKey

            if (
                !this.usesServerPersistence
                || !importUrl
                || !markerKey
            ) {
                return null
            }

            return {
                importUrl,
                markerKey,
            }
        },

        normalizeLocalRoutineImportSteps(
            rawSteps
        ) {
            if (
                !Array.isArray(rawSteps)
                || rawSteps.length === 0
            ) {
                return null
            }

            const normalizedSteps = rawSteps
                .slice(0, this.maxSteps ?? 10)
                .map(step => {
                    const mode =
                        normalizeMode(step.mode)

                    return {
                        name:
                            String(step.name ?? '')
                                .trim(),

                        bpm:
                            Number(step.bpm),

                        mode,

                        duration_seconds:
                            mode === 'timer'
                                ? Number(
                                    step.duration_seconds
                                )
                                : null,

                        time_signature_numerator:
                            [2, 3, 4].includes(
                                Number(
                                    step.time_signature_numerator
                                )
                            )
                                ? Number(
                                    step.time_signature_numerator
                                )
                                : 4,

                        time_signature_denominator: 4,
                    }
                })

            const hasInvalidStep =
                normalizedSteps.some(step => {
                    if (
                        step.name.length === 0
                        || step.name.length > 255
                    ) {
                        return true
                    }

                    if (
                        !Number.isInteger(step.bpm)
                            || step.bpm < MIN_BPM
                            || step.bpm > MAX_BPM
                    ) {
                        return true
                    }

                    if (
                        ![
                            'timer',
                            'classic',
                        ].includes(step.mode)
                    ) {
                        return true
                    }

                    if (
                        step.mode === 'timer'
                        && (
                            !Number.isInteger(
                                step.duration_seconds
                            )
                            || step.duration_seconds < 1
                            || step.duration_seconds > 300
                        )
                    ) {
                        return true
                    }

                    return false
                })

            if (hasInvalidStep) {
                return null
            }

            return normalizedSteps
        },

        getPendingLocalRoutineImport() {
            const config = this.getLocalRoutineImportConfig()

            if (!config) { return null }

            const saved = localStorage.getItem(this.storageKey)

            if (!saved) { return null }

            let rawSteps

            try { rawSteps = JSON.parse(saved) } catch { return null }

            const steps = this.normalizeLocalRoutineImportSteps(rawSteps)

            if (!steps) { return null }

            /*
            * La firma representa exactamente la versión
            * actual de los ejercicios Free.
            */
            const signature = JSON.stringify(steps)
            const handledSignature = localStorage.getItem(config.markerKey)

            /*
            * Esta versión ya fue importada o rechazada
            * explícitamente por el usuario.
            */
            if (handledSignature === signature) { return null }

            return {
                type: handledSignature === null
                    ? 'first_import'
                    : 'update',
                    
                signature,
                steps,
            }
        },

        keepServerRoutine(pending) {
            const config =
                this.getLocalRoutineImportConfig()

            if (
                !config
                || !pending?.signature
            ) {
                return null
            }

            /*
            * El usuario decidió conservar el servidor.
            * Registramos únicamente esta versión Free.
            */
            localStorage.setItem(
                config.markerKey,
                pending.signature
            )

            return 'kept'
        },

        prepareLocalRoutineImport() {
            this.localRoutineImportError = null

            const pending = this.getPendingLocalRoutineImport()

            if (!pending) {
                this.pendingLocalRoutineImport = null
                this.isLocalRoutineImportOpen = false

                return null
            }

            this.pendingLocalRoutineImport = pending
            this.isLocalRoutineImportOpen = true

            return pending
        },

        closeLocalRoutineImport() {
            this.isLocalRoutineImportOpen = false
            this.pendingLocalRoutineImport = null
            this.localRoutineImportError = null
        },

        async resolveLocalRoutineImport(
            decision
        ) {
            this.localRoutineImportLimitReached = false

            if (
                this.isLocalRoutineImportBusy
                || !this.pendingLocalRoutineImport
            ) {
                return null
            }

            this.isLocalRoutineImportBusy = true
            this.localRoutineImportError = null

            const pending = this.pendingLocalRoutineImport

            try {
                let result

                if (decision === 'use_free') {
                    result =
                        await this.importLocalRoutine(
                            pending
                        )
                } else if (
                    decision === 'keep_server'
                ) {
                    result = this.keepServerRoutine(pending)
                } else {
                    this.localRoutineImportError =
                        'The selected import option is invalid.'

                    return 'failed'
                }

                if (
                    result === 'imported'
                    || result === 'kept'
                ) {
                    this.closeLocalRoutineImport()

                    return result
                }

                if (result === 'trial_routine_limit') {
                    this.localRoutineImportLimitReached = true
                    this.localRoutineImportError = null

                    return result
                }

                this.localRoutineImportError =
                    'Your Free exercises could not be processed. Please try again.'

                return result ?? 'failed'
            } catch (error) {
                console.error(
                    'Could not resolve local exercises.',
                    error
                )

                this.localRoutineImportError =
                    'Your Free exercises could not be processed. Please try again.'

                return 'failed'
            } finally {
                this.isLocalRoutineImportBusy =
                    false
            }
        },

        async importLocalRoutine(pending) {
            const config =
                this.getLocalRoutineImportConfig()

            /*
            * Exigir pending es intencional.
            *
            * La llamada automática antigua desde init(),
            * que no pasa argumentos, no importará nada.
            */
            if (
                !config
                || !pending?.signature
                || !Array.isArray(pending.steps)
            ) {
                return null
            }

            /*
            * Evita aceptar un objeto pending alterado
            * o accidentalmente inconsistente.
            */
            if (
                JSON.stringify(pending.steps)
                !== pending.signature
            ) {
                return 'failed'
            }

            try {
                const response = await fetch(
                    config.importUrl,
                    {
                        method: 'POST',
                        credentials: 'same-origin',

                        headers: {
                            'Content-Type':
                                'application/json',

                            Accept:
                                'application/json',

                            'X-CSRF-TOKEN':
                                document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    ?.content ?? '',
                        },

                        body: JSON.stringify({
                            steps:
                                pending.steps,
                        }),
                    }
                )

                const data = await response
                    .json()
                    .catch(() => ({}))

                if (
                    response.ok
                    && data.status === 'imported'
                ) {
                    /*
                    * Guardamos la firma exacta que
                    * acaba de ser importada.
                    */
                    localStorage.setItem(
                        config.markerKey,
                        pending.signature
                    )

                    return 'imported'
                }

                if (
                    response.status === 409
                    && data.reason === 'trial_routine_limit'
                ) {
                    return 'trial_routine_limit'
                }

                console.error(
                    'Could not import local exercises.',
                    data
                )

                return 'failed'
            } catch (error) {
                console.error(
                    'Could not import local exercises.',
                    error
                )

                return 'failed'
            }
        },

        // APP RESET
        requestClearAllAppStorage() {
            this.showResetAppModal = true
        },

        closeResetAppModal() {
            this.showResetAppModal = false
        },

        clearAllAppStorage() {
            this.stop?.()
            this.resetAudio?.()

            localStorage.removeItem(this.storageKey)
            localStorage.removeItem(this.recentSessionsStorageKey)

            this.steps = defaultSteps()
            this.metronome = defaultMetronome()

            this.recentSessions = {
                classic: [],
                timer: [],
            }

            this.currentIndex = 0
            this.activeExerciseIndex = null
            this.remaining = null

            this.saveToLocalStorage?.()
            this.saveRecentSessions?.()

            this.showResetAppModal = false
        }
    }
}