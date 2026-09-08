function copyPattern(pattern = []) {
    return pattern.map(beat => ({
        ...beat,
        subdivisions: (beat.subdivisions ?? []).map(
            subdivision => ({ ...subdivision })
        ),
    }))
}

export function randomizerPersistence() {
    return {
        randomizerSavedSessions: [],
        randomizerSessionName: '',

        randomizerDraft: {
            origin: 'generated',
            sourceId: null,
        },

        isRandomizerSessionsDialogOpen: false,
        randomizerPendingRenameId: null,
        randomizerRenameName: '',

        getRandomizerSessionPayload() {
            if (!this.randomizerResult) {
                return null
            }

            return {
                name:
                    this.randomizerSessionName
                    || this.getRandomizerResultLabel(),

                bpm: Number(this.metronome.bpm),
                root: this.randomizerResult.root,
                scale: this.randomizerResult.scale,
                playback_mode:
                    this.randomizerPlaybackMode,

                numerator:
                    this.randomizerPulse
                        .timeSignature.numerator,

                denominator:
                    this.randomizerPulse
                        .timeSignature.denominator,

                subdivision:
                    this.randomizerPulse.subdivision,

                grouping: [
                    ...this.randomizerPulse.grouping,
                ],

                pattern: copyPattern(
                    this.randomizerPulse.pattern
                ),
            }
        },

        async requestRandomizerSession(
            url,
            {
                method = 'GET',
                payload = null,
            } = {}
        ) {
            const headers = {
                'Accept': 'application/json',
            }

            if (payload !== null) {
                headers['Content-Type'] =
                    'application/json'
            }

            if (method !== 'GET') {
                headers['X-CSRF-TOKEN'] = document
                    .querySelector(
                        'meta[name="csrf-token"]'
                    )
                    .content
            }

            const response = await fetch(url, {
                method,
                headers,
                ...(payload !== null && {
                    body: JSON.stringify(payload),
                }),
            })

            if (!response.ok) {
                console.error(
                    'Randomized session request failed.',
                    await response.json()
                )

                return null
            }

            return await response.json()
        },

        async loadRandomizedSessions() {
            const url = this.$root.dataset
                .randomizedSessionsIndexUrl

            if (!url) {
                return false
            }

            const sessions =
                await this.requestRandomizerSession(url)

            if (!sessions) {
                return false
            }

            this.randomizerSavedSessions = sessions

            return true
        },

        async storeRandomizedSession() {
            const payload =
                this.getRandomizerSessionPayload()

            if (!payload) {
                return null
            }

            return await this.requestRandomizerSession(
                this.$root.dataset
                    .randomizedSessionsStoreUrl,
                {
                    method: 'POST',
                    payload,
                }
            )
        },

        async updateRandomizedSession(
            id,
            payload =
                this.getRandomizerSessionPayload()
        ) {
            if (!payload) {
                return null
            }

            const url = this.$root.dataset
                .randomizedSessionsUpdateUrl
                .replace('__ID__', id)

            return await this.requestRandomizerSession(
                url,
                {
                    method: 'PATCH',
                    payload,
                }
            )
        },

        async saveRandomizedSession() {
            if (!this.randomizerResult) {
                return false
            }

            let savedSession

            if (this.randomizerDraft.origin === 'saved') {
                savedSession =
                    await this.updateRandomizedSession(
                        this.randomizerDraft.sourceId
                    )
            } else {
                savedSession =
                    await this.storeRandomizedSession()
            }

            if (!savedSession) {
                this.showToast(
                    'Could not save session',
                    'error'
                )
                return false
            }

            const index =
                this.randomizerSavedSessions.findIndex(
                    session =>
                        session.id === savedSession.id
                )

            if (index === -1) {
                this.randomizerSavedSessions.push(
                    savedSession
                )
            } else {
                this.randomizerSavedSessions.splice(
                    index,
                    1,
                    savedSession
                )
            }

            this.randomizerDraft = {
                origin: 'saved',
                sourceId: savedSession.id,
            }

            this.randomizerSessionName =
                savedSession.name

            this.showToast('Session saved', 'success')

            return true
        },

        async saveRandomizedSessionAs() {
            if (!this.randomizerResult) {
                return false
            }

            const previousName =
                this.randomizerSessionName

            this.randomizerSessionName = ''

            const savedSession =
                await this.storeRandomizedSession()

            if (!savedSession) {
                this.randomizerSessionName =
                    previousName

                this.showToast(
                    'Could not save session as new',
                    'error'
                )
                return false
            }

            this.randomizerSavedSessions.push(
                savedSession
            )

            this.randomizerDraft = {
                origin: 'saved',
                sourceId: savedSession.id,
            }

            this.randomizerSessionName =
                savedSession.name

            this.showToast(
                'Session saved as new',
                'success'
            )

            return true
        },

        loadRandomizedSession(id) {
            const saved =
                this.randomizerSavedSessions.find(
                    session => session.id === id
                )

            if (!saved) {
                return false
            }

            this.randomizerResult = {
                bpm: Number(saved.bpm),
                root: saved.root,
                scale: saved.scale,
                timeSignature: {
                    ...saved.timeSignature,
                },
            }

            this.randomizerPulse = {
                timeSignature: {
                    ...saved.timeSignature,
                },
                subdivision:
                    Number(saved.subdivision),
                grouping: [...saved.grouping],
                pattern: copyPattern(saved.pattern),
            }

            this.metronome.bpm = Number(saved.bpm)
            this.randomizerPlaybackMode =
                saved.playbackMode

            this.randomizerSessionName = saved.name
            this.randomizerDraft = {
                origin: 'saved',
                sourceId: saved.id,
            }

            this.currentBeat = 1
            this.currentSubdivision = 0

            if (this.isPlaying) {
                this.restartMetronome()
            }

            this.closeRandomizerSessionsDialog()

            return true
        },

        async destroyRandomizedSession(id) {
            const url = this.$root.dataset
                .randomizedSessionsDestroyUrl
                .replace('__ID__', id)

            const deleted =
                await this.requestRandomizerSession(
                    url,
                    { method: 'DELETE' }
                )

            if (!deleted) {
                this.showToast(
                    'Could not delete session',
                    'error'
                )
                return false
            }

            this.randomizerSavedSessions =
                this.randomizerSavedSessions.filter(
                    session => session.id !== id
                )

            if (
                this.randomizerDraft.origin === 'saved'
                && this.randomizerDraft.sourceId === id
            ) {
                this.randomizerDraft = {
                    origin: 'generated',
                    sourceId: null,
                }
                this.randomizerSessionName = ''
            }

            this.showToast(
                'Session deleted',
                'success'
            )

            return true
        },

        confirmDestroyRandomizedSession(session) {
            this.closeRandomizerSessionsDialog()

            this.openConfirmModal({
                title: 'Delete session?',
                message:
                    `Delete "${session.name}" permanently?`,
                confirmLabel: 'Delete',
                action: () =>
                    this.destroyRandomizedSession(
                        session.id
                    ),
            })
        },

        async renameRandomizedSession() {
            const id = Number(
                this.randomizerPendingRenameId
            )
            const name =
                this.randomizerRenameName.trim()

            if (!id || !name) {
                return false
            }

            const renamed =
                await this.updateRandomizedSession(
                    id,
                    { name }
                )

            if (!renamed) {
                this.showToast(
                    'Could not rename session',
                    'error'
                )
                return false
            }

            const index =
                this.randomizerSavedSessions
                    .findIndex(
                        session =>
                            session.id === renamed.id
                    )

            if (index !== -1) {
                this.randomizerSavedSessions.splice(
                    index,
                    1,
                    renamed
                )
            }

            if (
                this.randomizerDraft.origin === 'saved'
                && this.randomizerDraft.sourceId
                    === renamed.id
            ) {
                this.randomizerSessionName =
                    renamed.name
            }

            this.cancelRandomizerRename()
            this.showToast(
                'Session renamed',
                'success'
            )

            return true
        },

        startRandomizerRename(session) {
            this.randomizerPendingRenameId =
                session.id
            this.randomizerRenameName =
                session.name
        },

        cancelRandomizerRename() {
            this.randomizerPendingRenameId = null
            this.randomizerRenameName = ''
        },

        openRandomizerSessionsDialog() {
            this.isRandomizerSessionsDialogOpen = true
            this.$nextTick(() => {
                this.$refs
                    .randomizerSessionsDialog
                    ?.showModal()
            })
        },

        closeRandomizerSessionsDialog() {
            this.cancelRandomizerRename()
            this.isRandomizerSessionsDialogOpen = false
            this.$refs
                .randomizerSessionsDialog
                ?.close()
        },
    }
}
