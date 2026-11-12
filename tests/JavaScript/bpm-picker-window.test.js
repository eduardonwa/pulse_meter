import assert from 'node:assert/strict'
import test from 'node:test'
import { readFileSync } from 'node:fs'
import { numberPicker } from '../../resources/js/metronome/number-picker.js'

const options = Array.from({ length: 371 }, (_, i) => i + 30)

function pickerAt(bpm = 100) {
    let value = bpm
    const picker = numberPicker({
        options,
        getValue: () => value,
        setValue: next => { value = next },
    })
    return { picker, getValue: () => value }
}

test('BPM picker initially renders a limited range around the selected value', () => {
    const { picker } = pickerAt()
    picker.updateWindowForIndex(picker.getCurrentIndex())

    assert.equal(picker.visibleOptions.length, 31)
    assert.ok(picker.visibleOptions.includes(100))
    assert.equal(
        picker.topSpacerHeight + picker.visibleOptions.length * picker.optionHeight + picker.bottomSpacerHeight,
        options.length * picker.optionHeight,
    )
})

test('BPM picker window preserves both ends of the 30–400 range', () => {
    const { picker } = pickerAt()
    picker.updateWindowForIndex(0)
    assert.equal(picker.visibleOptions[0], 30)

    picker.updateWindowForIndex(370)
    assert.equal(picker.visibleOptions.at(-1), 400)
    assert.equal(picker.visibleOptions.length, 31)
    assert.equal(picker.bottomSpacerHeight, 0)
})

test('BPM scroll window follows full-range scroll offsets', () => {
    const { picker } = pickerAt()
    const scroller = { clientHeight: 132, scrollTop: 365 * 44 }
    picker.$refs = { scroller }
    picker.updateWindowFromScroll()

    assert.ok(picker.visibleOptions.includes(395))
    assert.equal(picker.visibleOptions.length, 31)
})

test('BPM picker selection still supports the maximum tempo', () => {
    const { picker, getValue } = pickerAt()
    picker.scrollToIndex = index => picker.updateWindowForIndex(index)
    picker.select(400)
    assert.equal(getValue(), 400)
    assert.ok(picker.visibleOptions.includes(400))
})

test('both picker templates render a bounded option window with scroll spacers', () => {
    const template = readFileSync(
        new URL('../../resources/views/components/inputs/number-picker.blade.php', import.meta.url),
        'utf8',
    )
    assert.equal((template.match(/x-for="option in visibleOptions"/g) ?? []).length, 2)
    assert.equal((template.match(/topSpacerHeight/g) ?? []).length, 2)
    assert.equal((template.match(/bottomSpacerHeight/g) ?? []).length, 2)
    assert.equal((template.match(/updateWindowFromScroll/g) ?? []).length, 2)
})
