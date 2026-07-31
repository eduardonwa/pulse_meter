export function pulseState(pulsePresets = []) {
    return {
        // PRESETS
        pulsePresets,
        
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
            origin: 'new',
            sourceId: null,
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
        
        patternPendingRenameId: null,
        patternRenameName: '',

        // TABS
        activePatternTab: null,
    }
}