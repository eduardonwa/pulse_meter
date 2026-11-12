import {
    Livewire,
    Alpine,
} from '../../vendor/livewire/livewire/dist/livewire.esm'

import {
    initializeProductAnalytics,
} from './analytics/product-events'
import { registerArticleSearch } from './article-search'

import './trial-mode'

window.Alpine = Alpine

registerArticleSearch(Alpine)

void initializeProductAnalytics()

Livewire.start()
