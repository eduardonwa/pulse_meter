export function state(steps) {
    return {
        steps,
        storageKey: 'pulse_meter_routine',

        metronome: {
            bpm: 100,
            mode: 'timer',
            duration_seconds: 60,
        },

        currentIndex: 0,
        activeTab: 'sessions',

        isPlaying: false,
        audioContext: null,
        intervalId: null,
        timerId: null,
        remaining: null,

        activeExerciseIndex: null,
        autoAdvance: true,
        maxSteps: 5,

        beatsPerMeasure: 4,
        currentBeat: 1,

        minutesOptions: [0, 1, 2, 3, 4, 5],
        secondsOptions: Array.from({ length: 60 }, (_, i) => i),
        bpmOptions: Array.from({ length: 271 }, (_, i) => i + 30),

        stepFormMode: 'create',
        stepFormIndex: null,

        stepForm: {
            name: '',
            bpm: 100,
            mode: 'timer',
            duration_seconds: 60,
        },

        stepFormMinutes: 1,
        stepFormSeconds: 0,

        dawProfiles: {
            ableton: {
                label: 'Ableton',
                click: '/audio/click-profiles/ableton/click.wav',
                accent: '/audio/click-profiles/ableton/accent.wav',
                finish: '/audio/click-profiles/ableton/accent.wav',
                beatsPerMeasure: 4,
            },

            cubase: {
                label: 'Cubase',
                click: '/audio/click-profiles/cubase/click.wav',
                accent: '/audio/click-profiles/cubase/accent.wav',
                finish: '/audio/click-profiles/cubase/accent.wav',
                beatsPerMeasure: 4,
            },
        },

        activeDawProfileKey: localStorage.getItem('pulse_meter_daw_profile') ?? 'cubase',

        clickBuffer: null,
        accentBuffer: null,
        finishBuffer: null,
    }
}