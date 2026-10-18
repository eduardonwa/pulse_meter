import test from 'node:test'
import assert from 'node:assert/strict'
import { scrollActiveBeatIntoView } from '../../resources/js/metronome/beat-strip.js'

function strip(width, offsets, scrollLeft = 0) {
    const element = {
        clientWidth: width,
        scrollLeft,
        getBoundingClientRect: () => ({ left: 0, right: width }),
    }
    element.children = offsets.map(left => ({
        getBoundingClientRect: () => ({
            left: left - element.scrollLeft,
            right: left - element.scrollLeft + 28,
        }),
    }))
    return element
}

test('does not scroll when the active beat is centered', () => {
    const element = strip(120, [2, 46, 90])
    scrollActiveBeatIntoView(element, 2)
    assert.equal(element.scrollLeft, 0)
})

test('centers the active beat before it reaches the right edge', () => {
    const element = strip(120, [0, 44, 88, 132, 176])
    scrollActiveBeatIntoView(element, 4)
    assert.equal(element.scrollLeft, 86)
    scrollActiveBeatIntoView(element, 4)
    assert.equal(element.scrollLeft, 44)
})

test('reveals an earlier beat when the measure wraps', () => {
    const element = strip(120, [0, 44, 88, 132, 176], 84)
    scrollActiveBeatIntoView(element, 1)
    assert.equal(element.scrollLeft, -46) // Browser clamps this to 0 in the real DOM.
})

test('handles missing elements without throwing', () => {
    assert.doesNotThrow(() => scrollActiveBeatIntoView(null, 1))
    assert.doesNotThrow(() => scrollActiveBeatIntoView(strip(120, []), 4))
})
