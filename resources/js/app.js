import {
    Livewire,
    Alpine,
} from '../../vendor/livewire/livewire/dist/livewire.esm'

import {
    initializeProductAnalytics,
} from './analytics/product-events'

import './metronome'

window.Alpine = Alpine

void initializeProductAnalytics()

Livewire.start()