import {
    Livewire,
    Alpine,
} from '../../vendor/livewire/livewire/dist/livewire.esm'

import {
    initializeProductAnalytics,
} from './analytics/product-events'
import { registerArticleSearch } from './article-search'

import './trial-mode'
import { registerPracticeSettings } from './practice-settings.js'
import { bootstrap } from './bootstrap.js'
import { loadPracticeModules } from './practice-module-loader.js'

window.Alpine = Alpine

registerArticleSearch(Alpine)
registerPracticeSettings(Alpine)

void initializeProductAnalytics()

// Keep a single entry script on every page. Practice modules must finish
// registering their Alpine factories BEFORE Livewire initializes Alpine.
if (document.querySelector('meta[name="dorelog-practice"]')) {
    performance.mark('dorelog:practice-bootstrap-start')
}

void bootstrap({
    needsPractice: Boolean(document.querySelector('meta[name="dorelog-practice"]')),
    loadPractice: () => loadPracticeModules({
        loadMetronome: () => import('./metronome'),
        loadAlphaTab: () => import('./alphatab-exercises'),
        // The onboarding tour is only used on the main Practice page.
        loadOnboarding: document.querySelector('[data-onboarding-storage-key]')
            ? () => import('./practice-onboarding.js')
            : null,
    }),
    startLivewire: () => Livewire.start(),
}).catch(error => {
    console.error('Dorelog failed to initialize:', error)
})
