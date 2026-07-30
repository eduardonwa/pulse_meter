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
    const savedDawProfile = localStorage.getItem('pulse_meter_daw_profile')
    const activeDawProfileKey = savedDawProfile ?? 'cubase'
    
    return {
        steps: steps?.length ? steps : defaultSteps(),
        
        storageKey: 'pulse_meter_routine',
        recentSessionsStorageKey: 'pulse_meter_recent_sessions',

        metronome: defaultMetronome(),
        defaultSteps,

        recentSessions: {
            classic: [],
            timer: [],
        },

        currentIndex: 0,
        activeTab: 'sessions',

        isPlaying: false,
        audioContext: null,
        intervalId: null,
        bpmChangeTimeoutId: null,
        timerId: null,
        remaining: null,
        showResetAppModal: false,

        activeExerciseIndex: null,
        maxSteps: 10,

        timeSignature: {
            numerator: 4,
            denominator: 4,
        },

        timeSignatures: [
            {
                id: '4-4-4',
                numerator: 4,
                denominator: 4,
                grouping: [4],
            },
            {
                id: '3-4-3',
                numerator: 3,
                denominator: 4,
                grouping: [3],
            },
            {
                id: '5-4-5',
                numerator: 5,
                denominator: 4,
                grouping: [5],
            },
            {
                id: '5-8-2-3',
                numerator: 5,
                denominator: 8,
                grouping: [2, 3],
            },
            {
                id: '7-8-2-3-2',
                numerator: 7,
                denominator: 8,
                grouping: [2, 3, 2],
            },
            {
                id: '9-8-3-3-3',
                numerator: 9,
                denominator: 8,
                grouping: [3, 3, 3],
            },
            {
                id: '11-8-3-3-3-2',
                numerator: 11,
                denominator: 8,
                grouping: [3, 3, 3, 2],
            },
        ],

        grouping: [4],

        pattern: [
            { sound: 'accent', groupStart: true },
            { sound: 'click',  groupStart: false },
            { sound: 'click',  groupStart: false },
            { sound: 'click',  groupStart: false },
        ],

        pulseDraft: {
            origin: 'preset',
            sourceId: '4-4-4',
            isDirty: false,
        },

        pulseBaseline: null,

        userPatterns: [],

        meterNumeratorOptions: Array.from(
            { length: 16 },
            (_, i) => i + 1
        ),

        meterDenominatorOptions: [
            2,
            4,
            8,
            16,
        ],

        editorTool: null,

        currentBeat: 1,

        toolTether: {
            active: false,
            startX: 0,
            startY: 0,
            endX: 0,
            endY: 0,
        },

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
            },

            cubase: {
                label: 'Cubase',
                click: '/audio/click-profiles/cubase/click.wav',
                accent: '/audio/click-profiles/cubase/accent.wav',
                finish: '/audio/click-profiles/cubase/accent.wav',
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

        isPatternDialogOpen: false,

        toast: {
            visible: false,
            message: '',
            type: 'info',
            timer: null,
        },

        showDeletePatternDialog: false,
        patternPendingDeleteId: null,
    }
}