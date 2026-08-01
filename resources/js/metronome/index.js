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
import { routinePersistence } from './routine-persistence.js'
import { routineDialog } from './routine-dialog.js'

window.routinePlayer = function (
    routine = null,
    pulsePresets = []
) {
    return compose(
        state(routine),
        lifecycle(),
        storage(),
        routinePersistence(),
        routineDialog(),
        audioEngine(),
        pulseEditor(pulsePresets),
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