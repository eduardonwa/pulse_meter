export function clickProfiles() {
    return {
        changeDawProfile(key) {
            if (!this.dawProfiles[key]) {
                return
            }

            this.activeDawProfileKey = key

            this.clickBuffer = null
            this.accentBuffer = null
            this.finishBuffer = null

            localStorage.setItem('pulse_meter_daw_profile', key)

            this.loadClickSounds?.()
        },
    }
}