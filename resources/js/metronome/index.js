import { compose } from './compose'
import { state } from './state'
import { lifecycle } from './lifecycle'
import { storage } from './storage'
import { audioEngine } from './audio-engine'
import { pulseEditor } from './pulse-editor/index.js'
import { freeSession } from './free-session'
import { timerDuration } from './timer-duration'
import { exercises } from './exercises'
import { numberPicker } from './number-picker'
import { clickProfiles } from './click-profiles'
import { recentSessions } from './recent-sessions'
import { analytics } from './analytics'
import { notifications } from './notifications.js'

window.routinePlayer = function (steps) {
    return compose(
        state(steps),
        lifecycle(),
        storage(),
        audioEngine(),
        pulseEditor(),
        freeSession(),
        timerDuration(),
        exercises(),
        clickProfiles(),
        recentSessions(),
        analytics(),
        notifications()
    )
}

window.numberPicker = numberPicker