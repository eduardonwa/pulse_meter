export function recentSessions() {
    return {
        loadRecentSessions() {
            const saved = localStorage.getItem(
                this.recentSessionsStorageKey
            )

            if (!saved) {
                return
            }

            try {
                const parsed = JSON.parse(saved)
                const hadManual = Array.isArray(parsed.manual)

                if (hadManual) {
                    parsed.classic = parsed.manual.map(session => ({
                        ...session,
                        type: 'classic',
                    }))

                    delete parsed.manual
                }

                this.recentSessions = {
                    classic: parsed.classic ?? [],
                    timer: parsed.timer ?? [],
                }

                if (hadManual) {
                    this.saveRecentSessions()
                }
            } catch {
                this.recentSessions = {
                    classic: [],
                    timer: [],
                }
            }
        },

        saveRecentSessions() {
            localStorage.setItem(
                this.recentSessionsStorageKey,
                JSON.stringify(this.recentSessions)
            )
        },

        createSessionId() {
            if (window.crypto?.randomUUID) {
                return window.crypto.randomUUID()
            }

            return `${Date.now()}-${Math.random().toString(36).slice(2)}`
        },

        saveCurrentSession() {
            if (this.activeSessionType !== 'free') {
                return
            }

            const session = {
                id: this.createSessionId(),
                type: this.metronome.mode,
                bpm: this.metronome.bpm,
                duration_seconds: this.metronome.mode === 'timer'
                    ? this.metronome.duration_seconds
                    : null,
                created_at: Date.now(),
            }

            const type = session.type

            this.recentSessions[type] = this.recentSessions[type].filter((item) => {
                return !this.isSameSession(item, session)
            })

            this.recentSessions[type].unshift(session)
            this.recentSessions[type] = this.recentSessions[type].slice(0, 5)

            this.saveRecentSessions()
        },

        clearRecentSessionsForCurrentMode() {
            const mode = this.metronome.mode

            this.openConfirmModal({
                title: `Clear recent ${mode} sessions?`,
                message: `This will delete all recent ${mode} sessions.`,
                confirmLabel: 'Clear',
                action: () => {
                    this.recentSessions[mode] = []
                    this.saveRecentSessions()
                },
            })
        },

        clearAllRecentSessions() {
            this.openConfirmModal({
                title: 'Clear all recent sessions?',
                message: 'This will delete all Classic and Timer recent sessions.',
                confirmLabel: 'Clear all',
                action: () => {
                    this.recentSessions = {
                        classic: [],
                        timer: [],
                    }

                    this.saveRecentSessions()
                },
            })
        },

        isSameSession(a, b) {
            return (
                a.type === b.type &&
                a.bpm === b.bpm &&
                a.duration_seconds === b.duration_seconds
            )
        },

        loadSession(session) {
            this.stop()

            this.metronome.mode = session.type
            this.metronome.bpm = session.bpm

            if (session.type === 'timer') {
                this.metronome.duration_seconds = session.duration_seconds
            }
        }
    }
}