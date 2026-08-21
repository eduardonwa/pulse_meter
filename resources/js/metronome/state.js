/* APP DEFAULTS */
/* ESTE ARCHIVO CONTIENE SOLAMENTE ESTADO TRANSVERSAL DE LA APP */

export function defaultSteps() {
    return [
        {
            name: 'Alternate Picking',
            bpm: 100,
            mode: 'timer',
            duration_seconds: 5,
        },
        {
            name: 'Legato',
            bpm: 80,
            mode: 'timer',
            duration_seconds: 5,
        },
        {
            name: 'Sweep Picking',
            bpm: 90,
            mode: 'timer',
            duration_seconds: 60,
        },
    ]
}

export function defaultMetronome() {
    return {
        bpm: 100,
        mode: 'timer',
        duration_seconds: 60,
    }
}

/* LIMITS */
export const MIN_BPM = 30
export const MAX_BPM = 400

export const DEFAULT_EXERCISE_COUNT_LIMIT = 10
export const DEFAULT_EXERCISE_DURATION_LIMIT = 300

export function state(practiceContext = null) {
    const configuredExerciseCountLimit =
        Number(
            practiceContext
                ?.limits
                ?.exercise_count
        )

    const configuredExerciseDurationLimit =
        Number(
            practiceContext
                ?.limits
                ?.exercise_duration_seconds
        )

    const maxSteps =
        Number.isInteger(
            configuredExerciseCountLimit
        )
        && configuredExerciseCountLimit > 0
            ? configuredExerciseCountLimit
            : DEFAULT_EXERCISE_COUNT_LIMIT

    const maxExerciseDurationSeconds =
        Number.isInteger(
            configuredExerciseDurationLimit
        )
        && configuredExerciseDurationLimit > 0
            ? configuredExerciseDurationLimit
            : DEFAULT_EXERCISE_DURATION_LIMIT

    const practiceMode = practiceContext?.mode === 'playlist'
        ? 'playlist'
        : 'routine'

    const activeRoutine =
        practiceContext?.active_routine ?? null

    const activePlaylist =
        practiceContext?.active_playlist ?? null

    const inferredPersistenceMode =
        activeRoutine?.id || activePlaylist?.id
            ? 'server'
            : 'local'

    const requestedPersistenceMode =
        practiceContext?.persistence

    const persistenceMode = [
        'local',
        'server',
        'template',
    ].includes(requestedPersistenceMode)
        ? requestedPersistenceMode
        : inferredPersistenceMode

    const usesServerPersistence =
        persistenceMode === 'server'

    const usesLocalPersistence =
        persistenceMode === 'local'

    const isTemplatePlayback =
        persistenceMode === 'template'

    const contextQueue =
        Array.isArray(practiceContext?.queue)
            ? practiceContext.queue
            : []

    const practiceGroups =
        Array.isArray(practiceContext?.groups)
            ? practiceContext.groups
            : []

    const initialSteps = usesLocalPersistence
        ? defaultSteps()
        : contextQueue

    const initialStep = initialSteps[0] ?? null

    const initialMetronome = {
        ...defaultMetronome(),
        ...(initialStep && {
            bpm: Number(initialStep.bpm),
            mode: initialStep.mode,
            duration_seconds: initialStep.mode === 'timer'
                ? Number(initialStep.duration_seconds ?? 60)
                : 60,
        }),
    }

    return {
        // PRACTICE MODE
        practiceMode,

        activeRoutine,
        activePlaylist,

        practiceGroups,

        practiceQueue:
            practiceMode === 'playlist'
                ? contextQueue
                : [],

        /*
         * Solamente Routine Mode puede modificar ejercicios
         * desde el panel principal.
         *
         * Free/localStorage también sigue siendo Routine Mode.
         */
        canManageExercises:
            practiceMode === 'routine'
            && !isTemplatePlayback,

        usesServerPersistence,

        // EXERCISE LIMITS
        maxSteps,
        maxExerciseDurationSeconds,

        /*
         * El reproductor existente puede seguir trabajando
         * con steps sin conocer todavía Playlist Mode.
         */
        steps: initialSteps,

        currentIndex: 0,

        // METRONOME
        metronome: initialMetronome,
        isPlaying: false,

        // NAVIGATION
        activeTab: 'exercises',

        // PICKING OPTIONS
        minutesOptions: [0, 1, 2, 3, 4, 5],

        secondsOptions:
            Array.from(
                { length: 60 },
                (_, i) => i
            ),

        bpmOptions: Array.from({ length: MAX_BPM - MIN_BPM + 1 }, (_, i) => i + MIN_BPM),

        // CONFIRMATION
        confirmModal: {
            isOpen: false,
            title: '',
            message: '',
            confirmLabel: 'Confirm',
            action: null
        },

        persistenceMode,
        usesServerPersistence,
        usesLocalPersistence,
        isTemplatePlayback,
    }
}