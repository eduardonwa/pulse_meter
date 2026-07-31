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
        mode: 'creative',
        duration_seconds: 60,
    }
}

export function state(steps) {
    return {
        // ROUTINE
        steps: steps?.length
            ? steps
            : defaultSteps(),
        
        currentIndex: 0,
        
        // METRONOME
        metronome: defaultMetronome(),
        isPlaying: false,

        // NAVIGATION
        activeTab: 'sessions',

        // PICKING OPTIONS
        minutesOptions: [0, 1, 2, 3, 4, 5],

        secondsOptions:
            Array.from({ length: 60 }, (_, i) => i),

        bpmOptions:
            Array.from({ length: 271 }, (_, i) => i + 30),

        // CONFIRMATION
        confirmModal: {
            isOpen: false,
            title: '',
            message: '',
            confirmLabel: 'Confirm',
            action: null,
        },
    }
}