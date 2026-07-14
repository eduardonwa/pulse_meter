import Alpine from 'alpinejs'
import focus from '@alpinejs/focus'

import {
    initializeProductAnalytics,
} from './analytics/product-events'

import './metronome'

Alpine.plugin(focus)

window.Alpine = Alpine

void initializeProductAnalytics()

Alpine.start()