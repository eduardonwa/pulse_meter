export function pulseEditor() {
    return {
        async setTimeSignature(signature) {
            this.timeSignature = {
                numerator: signature.numerator,
                denominator: signature.denominator,
            }

            this.grouping = [...signature.grouping]

            this.pattern = this.buildPatternFromGrouping(
                this.grouping
            )

            this.pulseDraft = {
                origin: 'preset',
                sourceId: signature.id,
                isDirty: false,
            }

            this.currentBeat = 1
            this.editorTool = null

            this.setPulseBaseline()

            if (this.intervalId) {
                await this.startMetronome(this.metronome.bpm)
            }
        },

        // GROUPINGS
        isGroupingAccent(beat) {
            let groupStart = 1

            for (const groupSize of this.grouping) {
                if (beat === groupStart) {
                    return true
                }

                groupStart += groupSize
            }

            return false
        },

        getGroupingTotal(grouping = this.grouping) {
            return grouping.reduce((total, groupSize) => {
                return total + groupSize
            }, 0)
        },

        getGroupingRemaining(grouping = this.grouping) {
            return this.timeSignature.numerator
                - this.getGroupingTotal(grouping)
        },

        getGroupingFromPattern() {
            const groupStarts = []

            for (
                let beat = 1;
                beat <= this.timeSignature.numerator;
                beat++
            ) {
                if (this.pattern[beat - 1]?.groupStart) {
                    groupStarts.push(beat)
                }
            }

            const grouping = []

            for (let i = 0; i < groupStarts.length; i++) {
                const start = groupStarts[i]

                const nextStart =
                    groupStarts[i + 1]
                    ?? this.timeSignature.numerator + 1

                grouping.push(nextStart - start)
            }

            return grouping
        },

        setGrouping(grouping) {
            const remaining = this.getGroupingRemaining(grouping)

            if (remaining < 0) {
                return false
            }

            this.grouping = [...grouping]

            this.pattern = this.buildPatternFromGrouping(
                this.grouping
            )

            this.syncPulseDirty()

            return true
        },

        setGroupStart(beat, isGroupStart) {
            if (
                beat < 1
                || beat > this.timeSignature.numerator
            ) {
                return false
            }

            // El beat 1 siempre inicia el primer grupo.
            if (beat === 1 && !isGroupStart) {
                return false
            }

            const patternBeat = this.pattern[beat - 1]

            if (!patternBeat) {
                return false
            }

            // Estamos creando un nuevo group start.
            if (isGroupStart && !patternBeat.groupStart) {
                patternBeat.groupStart = true

                // Sonido default de un nuevo group start.
                patternBeat.sound = 'accent'
            } else {
                // Si lo quitamos, conservamos el sonido que ya tenía.
                patternBeat.groupStart = isGroupStart
            }

            this.grouping = this.getGroupingFromPattern()

            this.syncPulseDirty()

            return true
        },

        getGroupIndexForBeat(beat) {
            let groupIndex = -1

            for (let i = 0; i < beat; i++) {
                if (this.pattern[i]?.groupStart) {
                    groupIndex++
                }
            }

            return groupIndex
        },

        isBeatFilled(beat) {
            return beat <= this.getGroupingTotal()
        },

        // PATTERNS
        buildPatternFromGrouping(grouping = this.grouping) {
            const pattern = []

            for (const groupSize of grouping) {
                for (let i = 0; i < groupSize; i++) {
                    pattern.push({
                        sound: i === 0
                            ? 'accent'
                            : 'click',

                        groupStart: i === 0,
                    })
                }
            }

            return pattern
        },

        setPatternBeat(beat, type) {
            const allowedTypes = [
                'accent',
                'click',
                'rest',
            ]

            if (!allowedTypes.includes(type)) {
                return false
            }

            if (
                beat < 1
                || beat > this.timeSignature.numerator
            ) {
                return false
            }

            const patternBeat = this.pattern[beat - 1]

            if (!patternBeat) {
                return false
            }

            patternBeat.sound = type

            this.syncPulseDirty()

            return true
        },

        cyclePatternBeat(beat) {
            const current = this.pattern[beat - 1]

            const nextType = {
                accent: 'click',
                click: 'rest',
                rest: 'accent',
            }

            this.setPatternBeat(
                beat,
                nextType[current?.sound] ?? 'accent'
            )
        },

        getPatternGroups() {
            const groups = []
            let currentGroup = []

            this.pattern.forEach((item, index) => {
                if (
                    item.groupStart
                    && currentGroup.length
                ) {
                    groups.push(currentGroup)
                    currentGroup = []
                }

                currentGroup.push({
                    ...item,
                    beat: index + 1,
                })
            })

            if (currentGroup.length) {
                groups.push(currentGroup)
            }

            return groups
        },

        startNewPattern() {
            this.cancelToolTether()

            this.timeSignature = {
                numerator: 4,
                denominator: 4,
            }

            // Desde cero = un solo grupo inicialmente.
            this.grouping = [4]

            this.pattern = this.buildPatternFromGrouping(
                this.grouping
            )

            this.pulseDraft = {
                origin: 'new',
                sourceId: null,
                isDirty: false,
            }

            this.currentBeat = 1

            this.setPulseBaseline()
        },

        createUserPattern() {
            const snapshot = this.getPulseSnapshot()

            return {
                id: this.createPulsePatternId(),

                name: `${snapshot.timeSignature.numerator}/${snapshot.timeSignature.denominator} — ${snapshot.grouping.join(' + ')}`,

                ...snapshot,
            }
        },

        savePulsePattern() {
            // Un preset nunca se sobrescribe.
            if (this.pulseDraft.origin === 'preset') {
                return false
            }

            // Pattern nuevo → crear.
            if (this.pulseDraft.origin === 'new') {
                const userPattern = this.createUserPattern()

                this.userPatterns.push(userPattern)

                this.pulseDraft = {
                    origin: 'user',
                    sourceId: userPattern.id,
                    isDirty: false,
                }

                this.setPulseBaseline()

                return true
            }

            // Pattern del usuario → actualizar.
            if (this.pulseDraft.origin === 'user') {
                const index = this.userPatterns.findIndex(
                    pattern => pattern.id === this.pulseDraft.sourceId
                )

                if (index === -1) {
                    return false
                }

                const snapshot = this.getPulseSnapshot()

                this.userPatterns[index] = {
                    ...this.userPatterns[index],

                    name: `${snapshot.timeSignature.numerator}/${snapshot.timeSignature.denominator} — ${snapshot.grouping.join(' + ')}`,

                    ...snapshot,
                }

                this.setPulseBaseline()

                return true
            }

            return false
        },

        savePulsePatternAs() {
            const userPattern = this.createUserPattern()

            this.userPatterns.push(userPattern)

            this.pulseDraft = {
                origin: 'user',
                sourceId: userPattern.id,
                isDirty: false,
            }

            this.setPulseBaseline()

            return true
        },

        createPulsePatternId() {
            return `pulse-${Date.now()}-${Math.random()
                .toString(36)
                .slice(2, 8)}`
        },

        loadUserPattern(id) {
            const userPattern = this.userPatterns.find(
                pattern => pattern.id === id
            )

            if (!userPattern) {
                return false
            }

            this.timeSignature = {
                ...userPattern.timeSignature,
            }

            this.grouping = [
                ...userPattern.grouping,
            ]

            this.pattern = userPattern.pattern.map(beat => ({
                ...beat,
            }))

            this.pulseDraft = {
                origin: 'user',
                sourceId: userPattern.id,
                isDirty: false,
            }

            this.currentBeat = 1
            this.editorTool = null

            this.setPulseBaseline()

            return true
        },

        // EDITOR TOOLS
        applyEditorTool(beat) {
            if (!this.editorTool) {
                return
            }

            if (this.editorTool === 'groupStart') {
                const patternBeat = this.pattern[beat - 1]

                if (!patternBeat) {
                    return
                }

                this.setGroupStart(
                    beat,
                    !patternBeat.groupStart
                )

                this.cancelToolTether()

                return
            }

            this.setPatternBeat(
                beat,
                this.editorTool
            )

            this.cancelToolTether()
        },

        // TOOL TETHER
        startToolTether(event) {
            const canHover = window.matchMedia(
                '(hover: hover) and (pointer: fine)'
            ).matches

            if (!canHover) {
                return
            }

            const rect =
                event.currentTarget.getBoundingClientRect()

            this.toolTether.startX =
                rect.left + rect.width / 2

            this.toolTether.startY =
                rect.top + rect.height / 2

            this.toolTether.endX =
                this.toolTether.startX

            this.toolTether.endY =
                this.toolTether.startY

            this.toolTether.active = true
        },

        getToolTetherPath() {
            const {
                startX,
                startY,
                endX,
                endY,
            } = this.toolTether

            const distance =
                Math.abs(endX - startX)

            const curve =
                Math.max(60, distance * 0.35)

            return `
                M ${startX} ${startY}
                C ${startX + curve} ${startY},
                  ${endX - curve} ${endY},
                  ${endX} ${endY}
            `
        },

        handleToolTetherPointerMove(event) {
            if (!this.toolTether.active) {
                return
            }

            this.toolTether.endX = event.clientX
            this.toolTether.endY = event.clientY
        },

        handleToolTetherClick(event) {
            if (!this.editorTool) {
                return
            }

            if (
                event.target.closest(
                    '[data-tether-trigger]'
                )
            ) {
                return
            }

            this.cancelToolTether()
        },

        cancelToolTether() {
            this.toolTether.active = false
            this.editorTool = null
        },

        // DRAFT
        getPulseSnapshot() {
            return {
                timeSignature: {
                    ...this.timeSignature,
                },

                grouping: [
                    ...this.grouping,
                ],

                pattern: this.pattern.map(beat => ({
                    ...beat,
                })),
            }
        },

        syncPulseDirty() {
            if (!this.pulseBaseline) {
                this.pulseDraft.isDirty = false
                return
            }

            const current =
                this.getPulseSnapshot()

            this.pulseDraft.isDirty =
                JSON.stringify(current)
                !== JSON.stringify(this.pulseBaseline)
        },

        setPulseBaseline() {
            this.pulseBaseline =
                this.getPulseSnapshot()

            this.pulseDraft.isDirty = false
        },

        selectPulseSource(value) {
            if (value === 'new') {
                this.startNewPattern()
                return
            }

            const [origin, id] = value.split(':')

            if (origin === 'preset') {
                const preset = this.timeSignatures.find(
                    signature => signature.id === id
                )

                if (!preset) {
                    return
                }

                this.setTimeSignature(preset)

                return
            }

            if (origin === 'user') {
                this.loadUserPattern(id)
            }
        },

        async setDraftMeter(numerator, denominator) {
            const parsedNumerator = Number(numerator)
            const parsedDenominator = Number(denominator)

            if (
                !Number.isInteger(parsedNumerator)
                || parsedNumerator < 1
            ) {
                return false
            }

            if (
                ![2, 4, 8, 16].includes(parsedDenominator)
            ) {
                return false
            }

            this.timeSignature = {
                numerator: parsedNumerator,
                denominator: parsedDenominator,
            }

            this.grouping = [
                parsedNumerator,
            ]

            this.pattern = this.buildPatternFromGrouping(
                this.grouping
            )

            this.currentBeat = 1

            this.syncPulseDirty()

            if (this.intervalId) {
                await this.startMetronome(
                    this.metronome.bpm
                )
            }

            return true
        },

        async setDraftNumerator(numerator) {
            const value = Number(numerator)

            if (
                !Number.isInteger(value)
                || value < 1
            ) {
                return false
            }

            const currentLength = this.pattern.length

            if (value > currentLength) {
                for (
                    let beat = currentLength;
                    beat < value;
                    beat++
                ) {
                    this.pattern.push({
                        sound: 'click',
                        groupStart: false,
                    })
                }
            }

            if (value < currentLength) {
                this.pattern = this.pattern.slice(
                    0,
                    value
                )
            }

            this.timeSignature.numerator = value

            this.grouping =
                this.getGroupingFromPattern()

            this.currentBeat = Math.min(
                this.currentBeat,
                value
            )

            this.syncPulseDirty()

            if (this.intervalId) {
                await this.startMetronome(
                    this.metronome.bpm
                )
            }

            return true
        },

        async setDraftDenominator(denominator) {
            const value = Number(denominator)

            if (![2, 4, 8, 16].includes(value)) {
                return false
            }

            this.timeSignature.denominator = value

            this.syncPulseDirty()

            if (this.intervalId) {
                await this.startMetronome(
                    this.metronome.bpm
                )
            }

            return true
        },

        // MODAL
        openPatternDialog() {
            this.isPatternDialogOpen = true

            this.$nextTick(() => {
                this.$refs.patternDialog?.showModal()
            })
        },

        closePatternDialog() {
            this.isPatternDialogOpen = false
            this.$refs.patternDialog?.close()
        },

        selectPulseSourceFromDialog(value) {
            this.selectPulseSource(value)
            this.closePatternDialog()
        },

        getCurrentPulseSourceLabel() {
            if (this.pulseDraft.origin === 'new') {
                return 'New Pattern'
            }

            if (this.pulseDraft.origin === 'preset') {
                const preset = this.timeSignatures.find(
                    item => item.id === this.pulseDraft.sourceId
                )

                if (!preset) {
                    return 'Pattern'
                }

                return `${preset.numerator}/${preset.denominator} — ${preset.grouping.join(' + ')}`
            }

            if (this.pulseDraft.origin === 'user') {
                const pattern = this.userPatterns.find(
                    item => item.id === this.pulseDraft.sourceId
                )

                return pattern?.name ?? 'Pattern'
            }

            return 'Pattern'
        },
    }
}