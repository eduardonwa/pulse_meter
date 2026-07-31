export function pulseState() {
    return {
        // PRESETS
        // TEMPORAL.
        // Desaparecerá cuando los presets vengan de Laravel.
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

        // METER
        timeSignature: {
            numerator: 4,
            denominator: 4,
        },
        
        grouping: [4],

        meterNumeratorOptions:
            Array.from({ length: 16 }, (_, i) => i + 1),
        
        meterDenominatorOptions: [
            2,
            4,
            8,
            16,
        ],

        // PATTERN
        pattern: [
            { sound: 'accent', groupStart: true },
            { sound: 'click', groupStart: false },
            { sound: 'click', groupStart: false },
            { sound: 'click', groupStart: false },
        ],

        userPatterns: [],

        // PLAYBACK
        creativePlaybackMode: 'click',
        pulseDownbeatEnabled: true,

        // DRAFT
        pulseDraft: {
            origin: 'preset',
            sourceId: '4-4-4',
            isDirty: false,
        },

        pulseBaseline: null,

        // EDITOR TOOLS
        editorTool: null,

        toolTether: {
            active: false,
            startX: 0,
            startY: 0,
            endX: 0,
            endY: 0,
        },

        // DIALOG
        isPatternDialogOpen: false,
        showDeletePatternDialog: false,
        patternPendingDeleteId: null,
    }
}