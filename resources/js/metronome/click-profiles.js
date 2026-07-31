export function clickProfiles() {
    const savedDawProfile =
        localStorage.getItem('pulse_meter_daw_profile')

    return {
        // PROFILE STATE
        dawProfiles: {
            ableton: {
                label: 'Ableton',
                click: '/audio/click-profiles/ableton/click.wav',
                accent: '/audio/click-profiles/ableton/accent.wav',
                finish: '/audio/click-profiles/ableton/accent.wav',
            },

            cubase: {
                label: 'Cubase',
                click: '/audio/click-profiles/cubase/click.wav',
                accent: '/audio/click-profiles/cubase/accent.wav',
                finish: '/audio/click-profiles/cubase/accent.wav',
            },
        },

        activeDawProfileKey: savedDawProfile ?? 'cubase',

        // PROFILE ACTIONS
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