import { driver } from 'driver.js'
import 'driver.js/dist/driver.css'

// A presentation-only tour. Never call exercise actions from onboarding.
const SELECTORS = [
    '[data-onboarding-exercise-list]',
    '[data-onboarding-exercise="classic"]',
    '[data-onboarding-exercise="drill"]',
    '[data-onboarding-exercise="classic"] .exercise-actions > button',
    '[data-onboarding-add-exercise]',
]

export const TOUR_COPY = [
    {
        title: 'Welcome to Dorelog!',
        description: 'Looks like this is your first time here. Want a quick tour of how Dorelog works?',
    },
    {
        title: 'Your exercise list',
        description: "This is your exercise list. We've added two examples to get you started. You can have up to 10 exercises.",
    },
    {
        title: 'Classic',
        description: "This exercise uses a regular metronome, just like the one you're probably familiar with. No timer.",
    },
    {
        title: 'Drill',
        description: 'This one works the same way, but adds a timer so you can practice for a set amount of time.',
    },
    {
        title: 'Oh, and one last thing!',
        description: "Click these three dots to edit any exercise.",
    },
    {
        title: "You're all set!",
        description: "That's it! Tap Add Exercise to create your own exercise and choose between Classic and Drill.",
    },
]

export function hasInitialExamples(steps) {
    return Array.isArray(steps)
        && steps.length === 2
        && steps[0]?.name === 'Alternate Picking'
        && steps[0]?.mode === 'classic'
        && steps[1]?.name === 'Legato'
        && steps[1]?.mode === 'drill'
}

function markSeen(key) {
    try {
        localStorage.setItem(key, '1')
    } catch {
        // Storage might be unavailable in a private browser session.
    }
}

function hasSeen(key) {
    try {
        return localStorage.getItem(key) !== null
    } catch {
        // Do not repeatedly interrupt users when storage is blocked.
        return true
    }
}

function addDeclineButton(popover, tour) {
    const footerButtons = popover.footerButtons
    if (!footerButtons || footerButtons.querySelector('[data-onboarding-decline]')) return

    const button = document.createElement('button')
    button.type = 'button'
    button.textContent = 'No thanks'
    button.className = 'dorelog-onboarding-decline'
    button.dataset.onboardingDecline = ''
    button.addEventListener('click', () => tour.destroy())
    footerButtons.prepend(button)
}

function startTour(root, storageKey) {
    const isMobile = window.matchMedia('(max-width: 767px)').matches
    const targets = SELECTORS.map((selector, index) =>
        index === 3 && isMobile
            ? '[data-onboarding-exercise="classic"] .mobile-exercise__summary'
            : selector
    )

    const steps = [
        {
            popover: {
                ...TOUR_COPY[0],
                showButtons: ['next', 'close'],
                nextBtnText: 'Take the tour',
                onPopoverRender(popover) {
                    addDeclineButton(popover, tour)
                },
            },
        },
        ...TOUR_COPY.slice(1).map((copy, index) => ({
            element: root.querySelector(targets[index]),
            disableActiveInteraction: true,
            popover: {
                ...copy,
                ...(index === 3 && isMobile
                    ? { description: "Open an exercise to find its Edit button and options. That's it!" }
                    : {}),
                side: index === 0 ? 'bottom' : 'top',
                align: 'center',
                ...(index === 4 ? { doneBtnText: 'Finish' } : {}),
            },
        })),
    ]

    const tour = driver({
        showProgress: true,
        smoothScroll: true,
        allowClose: true,
        overlayClickBehavior: 'close',
        popoverClass: 'dorelog-onboarding',
        steps,
        onDestroyed() {
            markSeen(storageKey)
        },
    })

    tour.drive()
}

window.addEventListener('dorelog:practice-ready', (event) => {
    const root = document.querySelector('[data-onboarding-storage-key]')
    if (!root || root.dataset.onboardingInitialized === 'true') return

    root.dataset.onboardingInitialized = 'true'

    const key = root.dataset.onboardingStorageKey
    const state = event.detail ?? {}

    if (!key
        || hasSeen(key)
        || state.practiceMode !== 'routine'
        || state.isPlaying
        || !hasInitialExamples(state.steps)
        || SELECTORS.filter((_, index) => index !== 3)
            .some(selector => !root.querySelector(selector))) {
        return
    }

    startTour(root, key)
})
