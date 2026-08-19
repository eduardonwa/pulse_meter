import { compose } from './compose'
import { state } from './state'
import { lifecycle } from './lifecycle'
import { storage } from './storage'
import { audioEngine } from './audio-engine'
import { pulseEditor } from './pulse-editor/index.js'
import { timerDuration } from './timer-duration'
import { exercises } from './exercises'
import { numberPicker } from './number-picker'
import { clickProfiles } from './click-profiles'
import { recentSessions } from './recent-sessions'
import { analytics } from './analytics'
import { notifications } from './notifications.js'
import { routinePersistence } from './routine-persistence.js'
import { playbackSession } from './playback-session.js'
import { routineTemplateActions } from '../routine-template-player.js'

window.routinePlayer = function (
    practiceContext = null,
    pulsePresets = []
) {
    return compose(
        state(practiceContext),
        lifecycle(),
        storage(),
        routinePersistence(),
        routineTemplateActions(),
        audioEngine(),
        pulseEditor(pulsePresets),
        playbackSession(),
        timerDuration(),
        exercises(),
        clickProfiles(),
        recentSessions(),
        analytics(),
        notifications()
    )
}

window.numberPicker = numberPicker