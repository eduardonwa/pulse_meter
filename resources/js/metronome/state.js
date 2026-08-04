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

export function state(practiceContext = null) {
    const practiceMode = practiceContext?.mode === 'playlist'
            ? 'playlist'
            : 'routine'

    const activeRoutine = practiceContext?.active_routine ?? null
    const activePlaylist = practiceContext?.active_playlist ?? null

     /*
     * Una rutina activa o una playlist activa significan
     * que los datos vienen del servidor.
     */
    const usesServerPersistence = Boolean(
        activeRoutine?.id
        || activePlaylist?.id
    )

    /*
     * En Routine Mode contiene los ejercicios de la rutina.
     * En Playlist Mode contiene la cola plana completa.
     */
    const serverQueue = Array.isArray(practiceContext?.queue)
            ? practiceContext.queue
            : []

    const practiceGroups = Array.isArray(practiceContext?.groups)
            ? practiceContext.groups
            : []

    return {
        // PRACTICE MODE
        practiceMode,

        activeRoutine,
        activePlaylist,

        practiceGroups,

        practiceQueue: practiceMode === 'playlist'
                ? serverQueue
                : [],

        /*
         * Solamente Routine Mode puede modificar ejercicios
         * desde el panel principal.
         *
         * Free/localStorage también sigue siendo Routine Mode.
         */
        canManageExercises: practiceMode === 'routine',

        usesServerPersistence,

        /*
         * El reproductor existente puede seguir trabajando
         * con steps sin conocer todavía Playlist Mode.
         */
        steps: usesServerPersistence
                ? serverQueue
                : defaultSteps(),

        currentIndex: 0,

        // METRONOME
        metronome: defaultMetronome(),
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

        bpmOptions:
            Array.from(
                { length: 271 },
                (_, i) => i + 30
            ),

        // CONFIRMATION
        confirmModal: {
            isOpen: false,
            title: '',
            message: '',
            confirmLabel: 'Confirm',
            action: null
        },
    }
}