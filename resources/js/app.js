import {
    Livewire,
    Alpine,
} from '../../vendor/livewire/livewire/dist/livewire.esm'

import {
    initializeProductAnalytics,
} from './analytics/product-events'

import './metronome'
import './trial-mode'
import './alphatab-exercises'

window.Alpine = Alpine

void initializeProductAnalytics()

Livewire.start()