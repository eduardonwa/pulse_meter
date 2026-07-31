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

        // DURATION LIMITS
        clampMetronomeDuration() {
            if (this.metronome.duration_seconds > 300) {
                this.metronome.duration_seconds = 300
            }

            if (this.metronome.duration_seconds < 1) {
                this.metronome.duration_seconds = 1
            }
        },
    }
}