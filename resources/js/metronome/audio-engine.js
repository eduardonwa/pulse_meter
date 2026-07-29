export function audioEngine() {
    return {
        ensureAudioContext() {
            this.audioContext ??= new AudioContext()
        },

        async loadAudioBuffer(path) {
            this.ensureAudioContext()

            const response = await fetch(path)

            if (!response.ok) {
                throw new Error(`Could not load audio file: ${path}`)
            }

            const arrayBuffer = await response.arrayBuffer()

            return await this.audioContext.decodeAudioData(arrayBuffer)
        },

        async loadClickSounds() {
            if (this.clickBuffer && this.accentBuffer && this.finishBuffer) {
                return
            }

            const profile = this.currentDawProfile

            if (!profile?.click || !profile?.accent || !profile?.finish) {
                console.warn('Missing click profile audio paths', profile)
                return
            }

            this.clickBuffer = await this.loadAudioBuffer(profile.click)
            this.accentBuffer = await this.loadAudioBuffer(profile.accent)
            this.finishBuffer = await this.loadAudioBuffer(profile.finish)
        },

        playBuffer(buffer, volume = 1) {
            this.ensureAudioContext()

            const now = this.audioContext.currentTime
            const source = this.audioContext.createBufferSource()
            const gain = this.audioContext.createGain()

            source.buffer = buffer

            gain.gain.setValueAtTime(volume, now)

            source.connect(gain)
            gain.connect(this.audioContext.destination)

            source.start(now)

            source.onended = () => {
                source.disconnect()
                gain.disconnect()
            }
        },

        async startMetronome(bpm) {
            clearInterval(this.intervalId)

            await this.loadClickSounds()

            this.currentBeat = 1
            this.playPatternBeat()

            this.intervalId = setInterval(() => {
                this.currentBeat++

                if (this.currentBeat > this.timeSignature.numerator) {
                    this.currentBeat = 1
                }

                this.playPatternBeat()
            }, this.getBeatIntervalMs(bpm))
        },

        getBeatIntervalMs(bpm) {
            const denominator = this.timeSignature.denominator

            return (60000 / bpm) * (4 / denominator)
        },

        async setTimeSignature(signature) {
            this.timeSignature = {
                numerator: signature.numerator,
                denominator: signature.denominator,
            }

            this.grouping = [...signature.grouping]

            this.pattern = this.buildPatternFromGrouping(
                this.grouping
            )

            this.currentBeat = 1
            this.editorTool = null

            if (this.intervalId) {
                await this.startMetronome(this.metronome.bpm)
            }
        },

        tick(isAccent = false) {
            const buffer = isAccent ? this.accentBuffer : this.clickBuffer

            if (!buffer) {
                return
            }

            this.playBuffer(buffer, isAccent ? 1 : 0.9)
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
            return this.timeSignature.numerator - this.getGroupingTotal(grouping)
        },

        getGroupingFromPattern() {
            const groupStarts = []

            for (let beat = 1; beat <= this.timeSignature.numerator; beat++) {
                if (this.pattern[beat - 1]?.groupStart) {
                    groupStarts.push(beat)
                }
            }

            const grouping = []

            for (let i = 0; i < groupStarts.length; i++) {
                const start = groupStarts[i]

                const nextStart =
                    groupStarts[i + 1] ?? this.timeSignature.numerator + 1

                grouping.push(nextStart - start)
            }

            return grouping
        },

        setGrouping(grouping) {
            const remaining = this.getGroupingRemaining(grouping)

            if (remaining < 0) {
                return false
            }

            this.grouping = grouping
            this.pattern = this.buildPatternFromGrouping(grouping)

            console.log('Grouping:', this.grouping)
            console.log('Pattern:', this.pattern)
            
            return true
        },

        setGroupStart(beat, isGroupStart) {
            if (beat < 1 || beat > this.timeSignature.numerator) {
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

            console.log('Grouping:', this.grouping)
            console.log('Pattern:', this.pattern)

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
                        sound: i === 0 ? 'accent' : 'click',
                        groupStart: i === 0,
                    })
                }
            }

            return pattern
        },

        playPatternBeat(beat = this.currentBeat) {
            const patternBeat = this.pattern[beat - 1]

            if (!patternBeat || patternBeat.sound === 'rest') {
                return
            }

            this.tick(patternBeat.sound === 'accent')
        },

        setPatternBeat(beat, type) {
            const allowedTypes = ['accent', 'click', 'rest']

            if (!allowedTypes.includes(type)) {
                return false
            }

            if (beat < 1 || beat > this.timeSignature.numerator) {
                return false
            }

            const patternBeat = this.pattern[beat - 1]

            if (!patternBeat) {
                return false
            }

            patternBeat.sound = type

            console.log(this.pattern)

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
                nextType[current] ?? 'accent'
            )
        },

        // TIME EDITOR 
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

                this.editorTool = null
                return
            }

            this.setPatternBeat(
                beat,
                this.editorTool
            )

            this.editorTool = null
            
            this.cancelToolTether()
        },

        getPatternGroups() {
            const groups = [];
            let currentGroup = [];

            this.pattern.forEach((item, index) => {
                if (item.groupStart && currentGroup.length) {
                    groups.push(currentGroup);
                    currentGroup = [];
                }

                currentGroup.push({
                    ...item,
                    beat: index + 1,
                });
            });

            if (currentGroup.length) {
                groups.push(currentGroup);
            }

            return groups;
        },

        startToolTether(event) {
            if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
                return;
            }

            const rect = event.currentTarget.getBoundingClientRect();

            this.toolTether.startX = rect.left + rect.width / 2;
            this.toolTether.startY = rect.top + rect.height / 2;

            this.toolTether.endX = this.toolTether.startX;
            this.toolTether.endY = this.toolTether.startY;

            this.toolTether.active = true;
        },

        getToolTetherPath() {
            const {
                startX,
                startY,
                endX,
                endY
            } = this.toolTether;

            const distance = Math.abs(endX - startX);
            const curve = Math.max(60, distance * 0.35);

            return `
                M ${startX} ${startY}
                C ${startX + curve} ${startY},
                  ${endX - curve} ${endY},
                  ${endX} ${endY}
            `;
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

            if (event.target.closest('[data-tether-trigger]')) {
                return
            }

            this.cancelToolTether()
        },

        cancelToolTether() {
            this.toolTether.active = false
            this.editorTool = null
        },

        // END ACTIVITIES
        async playFinishSound() {
            await this.loadClickSounds()

            this.playBuffer(this.finishBuffer, 1)
        },

        stopMetronome() {
            clearInterval(this.intervalId)

            this.intervalId = null
            this.currentBeat = 1
        },

        stop(reason = 'user') {
            const wasPlaying = this.isPlaying

            if (wasPlaying) {
                this.endPlaybackTracking(reason)
            }

            this.stopMetronome()

            clearInterval(this.timerId)

            this.timerId = null
            this.isPlaying = false
            this.remaining = null
            this.activeExerciseIndex = null
        },
    }
}