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
import { alphaTabExerciseControls } from './alphatab/index.js'

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

window.routineTemplateGuest = function ({
    steps = [],
} = {}) {
    const templateSteps =
        Array.isArray(steps)
            ? steps
            : []

    return compose(
        {
            steps: templateSteps,

            currentIndex: 0,
            activeExerciseIndex: null,

            metronome: {
                bpm: Number(
                    templateSteps[0]?.bpm ?? 100
                ),
            },
        },

        notifications(),
        routineTemplateActions()
    )
}

window.numberPicker = numberPicker

window.alphaTabExerciseControls = alphaTabExerciseControls