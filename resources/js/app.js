import {
    Livewire,
    Alpine,
} from '../../vendor/livewire/livewire/dist/livewire.esm'

import {
    initializeProductAnalytics,
} from './analytics/product-events'

import './metronome'
import './trial-mode'

window.Alpine = Alpine

void initializeProductAnalytics()

Livewire.start()