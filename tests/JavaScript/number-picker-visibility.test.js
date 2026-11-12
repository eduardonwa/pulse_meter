import test from 'node:test'
import assert from 'node:assert/strict'
import { numberPicker } from '../../resources/js/metronome/number-picker.js'

function setupPicker(initialBpm = 120) {
    const original = {
        ResizeObserver: globalThis.ResizeObserver,
        requestAnimationFrame: globalThis.requestAnimationFrame,
        window: globalThis.window,
        document: globalThis.document,
    }

    let observer
    let bpm = initialBpm

    const option = { offsetHeight: 32 }
    const scroller = {
        clientHeight: 0,
        scrollHeight: 0,
        scrollTop: 0,
        querySelector: () => option,
    }

    globalThis.ResizeObserver = class {
        constructor(callback) {
            this.callback = callback
            observer = this
        }
        observe(target) { assert.equal(target, scroller) }
        disconnect() { this.disconnected = true }
        notify() { this.callback() }
    }
    globalThis.requestAnimationFrame = callback => { callback(); return 1 }
    globalThis.window = new EventTarget()
    globalThis.document = new EventTarget()
    globalThis.document.visibilityState = 'visible'

    const picker = Object.assign(
        numberPicker({
            options: Array.from({ length: 371 }, (_, i) => i + 30),
            getValue: () => bpm,
            setValue: value => { bpm = value },
        }),
        {
            $refs: { scroller },
            $nextTick: callback => callback(),
        }
    )

    return {
        picker,
        scroller,
        observer: () => observer,
        getBpm: () => bpm,
        restore() {
            picker.destroy()
            Object.assign(globalThis, original)
        },
    }
}

test('restores selected BPM when an x-show picker becomes visible', () => {
    const env = setupPicker()
    try {
        env.picker.init()
        assert.equal(env.scroller.scrollTop, 0)

        env.scroller.clientHeight = 160
        env.scroller.scrollHeight = 12000
        env.observer().notify()

        assert.equal(env.scroller.scrollTop, (120 - 30) * 32)
        assert.equal(env.getBpm(), 120)
    } finally {
        env.restore()
    }
})

test('restores scroll on tab return without changing the selected BPM', () => {
    const env = setupPicker(90)
    try {
        env.scroller.clientHeight = 160
        env.scroller.scrollHeight = 12000
        env.picker.init()
        env.scroller.scrollTop = 0

        globalThis.document.dispatchEvent(new Event('visibilitychange'))

        assert.equal(env.scroller.scrollTop, (90 - 30) * 32)
        assert.equal(env.getBpm(), 90)
    } finally {
        env.restore()
    }
})

test('manual scrolling can still update BPM', () => {
    const env = setupPicker(120)
    try {
        env.scroller.clientHeight = 160
        env.scroller.scrollHeight = 12000
        env.picker.init()
        env.picker.hasSyncedInitialValue = true

        env.scroller.scrollTop = (121 - 30) * 32
        env.picker.syncFromScroll()

        assert.equal(env.getBpm(), 121)
    } finally {
        env.restore()
    }
})
