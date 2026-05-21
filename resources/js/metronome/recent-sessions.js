export function recentSessions() {
    return {
        loadRecentSessions() {
            const saved = localStorage.getItem(this.recentSessionsStorageKey)

            if (!saved) {
                return
            }

            try {
                this.recentSessions = JSON.parse(saved) ?? {
                    manual: [],
                    timer: [],
                }
            } catch {
                this.recentSessions = {
                    manual: [],
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

            const confirmed = window.confirm(
                `Do you want to clear recent ${mode} sessions?`
            )

            if (!confirmed) {
                return
            }

            this.recentSessions[mode] = []

            this.saveRecentSessions()
        },

        clearAllRecentSessions() {
            const confirmed = window.confirm(
                'Do you want to clear all recent sessions?'
            )

            if (!confirmed) {
                return
            }

            this.recentSessions = {
                manual: [],
                timer: [],
            }

            this.saveRecentSessions()
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