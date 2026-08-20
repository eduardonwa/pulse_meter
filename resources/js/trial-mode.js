let destroyTrialMode = null

function attachSessionId(
    form,
    trialSessionId
) {
    if (!form) {
        return
    }

    form.addEventListener('submit', () => {
        let input = form.querySelector(
            'input[name="session_id"]'
        )

        if (!input) {
            input = document.createElement('input')
            input.type = 'hidden'
            input.name = 'session_id'

            form.appendChild(input)
        }

        input.value = trialSessionId
    })
}

function initializeTrialMode() {
    /*
     * Destruimos la instancia anterior solamente
     * cuando el nuevo DOM ya está disponible.
     */
    destroyTrialMode?.()
    destroyTrialMode = null

    const trialStatus = document.querySelector(
        '[data-trial-mode-status]'
    )

    if (!trialStatus) {
        return null
    }

    const trialSessionId =
        globalThis.dorelogClientIds
            ?.getSessionId?.()

    if (!trialSessionId) {
        console.error(
            'DoreLog session_id could not be loaded.'
        )

        return null
    }

    const pauseForm = trialStatus.querySelector(
        '[data-trial-pause-form]'
    )

    const resumeForm = trialStatus.querySelector(
        '[data-trial-resume-form]'
    )

    attachSessionId(
        pauseForm,
        trialSessionId
    )

    attachSessionId(
        resumeForm,
        trialSessionId
    )

    /*
     * Paused sólo necesita el session_id
     * para Resume.
     */
    if (
        trialStatus.dataset.trialModeStatus
        !== 'active'
    ) {
        return null
    }

    const timeElement = trialStatus.querySelector(
        '[data-trial-time]'
    )

    const stateElement = trialStatus.querySelector(
        '[data-trial-state]'
    )

    const heartbeatUrl =
        trialStatus.dataset.heartbeatUrl

    if (
        !timeElement
        || !heartbeatUrl
    ) {
        return null
    }

    const csrfToken = document
        .querySelector(
            'meta[name="csrf-token"]'
        )
        ?.getAttribute('content')

    let remainingSeconds = Number(
        trialStatus.dataset.remainingSeconds
    )

    let heartbeatIntervalId = null
    let visualIntervalId = null

    let requestInProgress = false
    let waitingForVisibilitySync = false
    let blockedByOtherSession = false
    let isDestroyed = false

    function formatTime(totalSeconds) {
        const safeSeconds = Math.max(
            0,
            totalSeconds
        )

        const minutes = Math.floor(
            safeSeconds / 60
        )

        const seconds =
            safeSeconds % 60

        return `${
            String(minutes).padStart(2, '0')
        }:${
            String(seconds).padStart(2, '0')
        }`
    }

    function renderTime() {
        timeElement.textContent =
            formatTime(remainingSeconds)
    }

    async function sendTrialHeartbeat(
        event = 'heartbeat'
    ) {
        const isHiddenSignal =
            event === 'hidden'

        if (
            isDestroyed
            || (
                !isHiddenSignal
                && document.hidden
            )
            || (
                !isHiddenSignal
                && requestInProgress
            )
            || !csrfToken
        ) {
            return
        }

        if (!isHiddenSignal) {
            requestInProgress = true
        }

        try {
            const response = await fetch(
                heartbeatUrl,
                {
                    method: 'POST',
                    credentials: 'same-origin',

                    headers: {
                        Accept: 'application/json',
                        'Content-Type':
                            'application/json',
                        'X-CSRF-TOKEN':
                            csrfToken,
                    },

                    body: JSON.stringify({
                        event,
                        session_id:
                            trialSessionId,
                    }),

                    keepalive:
                        isHiddenSignal,
                }
            )

            const data =
                await response.json()

            if (isDestroyed) {
                return
            }

            if (
                response.status === 409
                && data.status
                    === 'active_elsewhere'
            ) {
                blockedByOtherSession = true
                waitingForVisibilitySync = true

                if (stateElement) {
                    stateElement.textContent =
                        'Other tab'
                }

                pauseForm?.remove()

                return
            }

            if (!response.ok) {
                throw new Error(
                    `Heartbeat request failed: ${
                        response.status
                    }`
                )
            }

            blockedByOtherSession = false

            if (stateElement) {
                stateElement.textContent =
                    'Running'
            }

            if (
                Number.isInteger(
                    data.remaining_seconds
                )
            ) {
                remainingSeconds =
                    data.remaining_seconds

                renderTime()
            }

            if (!isHiddenSignal) {
                waitingForVisibilitySync = false

                /*
                 * El servidor ya cambió el Trial
                 * a paused/completed/etc.
                 */
                if (data.status !== 'active') {
                    window.clearInterval(
                        heartbeatIntervalId
                    )

                    window.clearInterval(
                        visualIntervalId
                    )

                    window.location.reload()

                    return
                }
            }
        } catch (error) {
            console.error(
                'Trial heartbeat failed.',
                error
            )
        } finally {
            if (!isHiddenSignal) {
                requestInProgress = false
            }
        }
    }

    renderTime()

    /*
     * Contador visual.
     */
    visualIntervalId =
        window.setInterval(() => {
            if (
                isDestroyed
                || document.hidden
                || waitingForVisibilitySync
                || blockedByOtherSession
                || remainingSeconds <= 0
            ) {
                return
            }

            remainingSeconds -= 1

            renderTime()

            if (remainingSeconds === 0) {
                sendTrialHeartbeat()
            }
        }, 1_000)

    /*
     * Primer heartbeat.
     */
    sendTrialHeartbeat()

    /*
     * Heartbeat normal.
     */
    heartbeatIntervalId =
        window.setInterval(
            sendTrialHeartbeat,
            10_000
        )

    function handleVisibilityChange() {
        if (isDestroyed) {
            return
        }

        if (document.hidden) {
            waitingForVisibilitySync = true

            if (!blockedByOtherSession) {
                sendTrialHeartbeat('hidden')
            }

            return
        }

        sendTrialHeartbeat()
    }

    document.addEventListener(
        'visibilitychange',
        handleVisibilityChange
    )

    return () => {
        if (isDestroyed) {
            return
        }

        isDestroyed = true

        window.clearInterval(
            heartbeatIntervalId
        )

        window.clearInterval(
            visualIntervalId
        )

        document.removeEventListener(
            'visibilitychange',
            handleVisibilityChange
        )
    }
}

function bootTrialMode() {
    destroyTrialMode =
        initializeTrialMode()
}

/*
 * La nueva página YA llegó.
 *
 * initializeTrialMode() se encarga de matar
 * la instancia anterior y levantar la nueva.
 */
document.addEventListener(
    'livewire:navigated',
    bootTrialMode
)

/*
 * Carga normal sin wire:navigate.
 */
if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        bootTrialMode,
        {
            once: true,
        }
    )
} else {
    bootTrialMode()
}