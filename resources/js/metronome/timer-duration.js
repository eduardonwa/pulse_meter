export function timerDuration() {
    return {

        // DURATION STATE
        get activeDuration() {
            if (this.isPlaying && this.remaining !== null) {
                return this.remaining
            }

            return this.metronome.duration_seconds
        },

        // DURATION GETTERS
        get metronomeMinutes() {
            return Math.floor(this.activeDuration / 60)
        },
        
        get metronomeSeconds() {
            return this.activeDuration % 60
        },

        // DURATION SETTERS
        set metronomeMinutes(value) {
            const seconds = this.metronome.duration_seconds % 60

            this.metronome.duration_seconds = (Number(value) * 60) + seconds
            this.clampMetronomeDuration()
            this.remaining = null
        },

        set metronomeSeconds(value) {
            const minutes = Math.floor(this.metronome.duration_seconds / 60)

            this.metronome.duration_seconds = (minutes * 60) + Number(value)
            this.clampMetronomeDuration()
            this.remaining = null
        },

        get timerMinutesOptions() {
            const maxDuration = Number(this.maxExerciseDurationSeconds)

            const maxMinutes = Math.floor(maxDuration / 60)

            return Array.from(
                {
                    length: maxMinutes + 1,
                },
                (_, index) => index
            )
        },

        get timerSecondsOptions() {
            const maxDuration = Number(this.maxExerciseDurationSeconds)

            const selectedMinutes =
                Math.floor(
                    Number(
                        this.metronome.duration_seconds
                    ) / 60
                )

            const maxMinutes = Math.floor(maxDuration / 60)

            const remainingSeconds = maxDuration % 60

            const maxSeconds = selectedMinutes >= maxMinutes
                ? remainingSeconds
                : 59

            return Array.from({ length: maxSeconds + 1, }, (_, index) => index )
        },

        // DURATION LIMITS
        clampMetronomeDuration() {
            const maxDuration =
                Number(this.maxExerciseDurationSeconds)

            if (this.metronome.duration_seconds > maxDuration) {
                this.metronome.duration_seconds = maxDuration
            }

            if (this.metronome.duration_seconds < 1) {
                this.metronome.duration_seconds = 1
            }
        },
    }
}