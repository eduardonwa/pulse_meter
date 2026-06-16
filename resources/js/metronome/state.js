/* APP DEFAULTS */
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
            duration_seconds: null,
        },
    ]
}

export function defaultMetronome() {
    return {
        bpm: 100,
        mode: 'manual',
        duration_seconds: 60,
    }
}

export function state(steps) {    
    const savedDawProfile = localStorage.getItem('pulse_meter_daw_profile')
    const activeDawProfileKey = savedDawProfile ?? 'cubase'
    
    return {
        steps: steps?.length ? steps : defaultSteps(),
        
        storageKey: 'pulse_meter_routine',
        recentSessionsStorageKey: 'pulse_meter_recent_sessions',

        metronome: defaultMetronome(),
        defaultSteps,

        recentSessions: {
            manual: [],
            timer: [],
        },

        currentIndex: 0,
        activeTab: 'exercises',

        isPlaying: false,
        audioContext: null,
        intervalId: null,
        bpmChangeTimeoutId: null,
        timerId: null,
        remaining: null,
        showResetAppModal: false,

        activeExerciseIndex: null,
        maxSteps: 10,

        beatsPerMeasure: 4,
        currentBeat: 1,

        minutesOptions: [0, 1, 2, 3, 4, 5],
        secondsOptions: Array.from({ length: 60 }, (_, i) => i),
        bpmOptions: Array.from({ length: 271 }, (_, i) => i + 30),

        stepFormMode: 'create',
        stepFormIndex: null,
        isStepFormOpen: false,

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

        activeDawProfileKey,

        clickBuffer: null,
        accentBuffer: null,
        finishBuffer: null,

        activeSessionType: null,

        autoAdvance: true,
        isWaitingForNextExercise: false,
        nextExerciseIndex: null,

        isPracticeReviewOpen: false,
        practiceFeeling: null,
        practiceFeelingConfirmation: '',

        confirmModal: {
            isOpen: false,
            title: '',
            message: '',
            confirmLabel: 'Confirm',
            action: null,
        },
    }
}