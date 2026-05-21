export function clickProfiles() {
    return {
        changeDawProfile(key) {
            if (!this.dawProfiles[key]) {
                return
            }

            this.activeDawProfileKey = key

            const profile = this.dawProfiles[key]

            this.beatsPerMeasure = profile.beatsPerMeasure ?? this.beatsPerMeasure

            this.clickBuffer = null
            this.accentBuffer = null
            this.finishBuffer = null

            localStorage.setItem('pulse_meter_daw_profile', key)

            this.loadClickSounds?.()
        },
    }
}