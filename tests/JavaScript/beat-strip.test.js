import test from 'node:test'
import assert from 'node:assert/strict'
import { scrollActiveBeatIntoView } from '../../resources/js/metronome/beat-strip.js'

function strip(width, offsets, scrollLeft = 0) {
    return {
        offsetLeft: 0,
        clientWidth: width,
        scrollLeft,
        children: offsets.map(offsetLeft => ({ offsetLeft, offsetWidth: 28 })),
    }
}

test('does not scroll when active beat is already visible', () => {
    const element = strip(120, [0, 44, 88])
    scrollActiveBeatIntoView(element, 2)
    assert.equal(element.scrollLeft, 0)
})

test('scrolls right only when active beat leaves viewport', () => {
    const element = strip(120, [0, 44, 88, 132, 176])
    scrollActiveBeatIntoView(element, 4)
    assert.equal(element.scrollLeft, 40)
})

test('scrolls left when returning to an earlier beat', () => {
    const element = strip(120, [0, 44, 88, 132, 176], 84)
    scrollActiveBeatIntoView(element, 1)
    assert.equal(element.scrollLeft, 0)
})

test('handles missing elements without throwing', () => {
    assert.doesNotThrow(() => scrollActiveBeatIntoView(null, 1))
    assert.doesNotThrow(() => scrollActiveBeatIntoView(strip(120, []), 4))
})
