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

        newStep: {
            name: '',
            bpm: 100,
            mode: 'timer',
            duration_seconds: 60,
        },

        newStepMinutes: 1,
        newStepSeconds: 0,

        editStepIndex: null,

        editStep: {
            name: '',
            bpm: 100,
            mode: 'timer',
            duration_seconds: 60,
        },

        editStepMinutes: 1,
        editStepSeconds: 0,
    }
}