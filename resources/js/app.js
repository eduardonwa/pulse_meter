import {
    Livewire,
    Alpine,
} from '../../vendor/livewire/livewire/dist/livewire.esm'

import {
    initializeProductAnalytics,
} from './analytics/product-events'
import { registerArticleSearch } from './article-search'

import './trial-mode'
import { bootstrap } from './bootstrap.js'

window.Alpine = Alpine

registerArticleSearch(Alpine)

void initializeProductAnalytics()

// Keep a single entry script on every page. Practice modules must finish
// registering their Alpine factories BEFORE Livewire initializes Alpine.
if (document.querySelector('meta[name="dorelog-practice"]')) {
    performance.mark('dorelog:practice-bootstrap-start')
}

void bootstrap({
    needsPractice: Boolean(document.querySelector('meta[name="dorelog-practice"]')),
    loadPractice: async () => {
        await Promise.all([
            import('./metronome'),
            import('./alphatab-exercises'),
        ])
    },
    startLivewire: () => Livewire.start(),
}).catch(error => {
    console.error('Dorelog failed to initialize:', error)
})
