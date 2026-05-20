export function numberPicker(config) {
    return {
        options: config.options,
        getValue: config.getValue,
        setValue: config.setValue,
        disabled: config.disabled ?? (() => false),
        format: config.format ?? ((value) => value),

        lastSyncedValue: null,

        init() {
            this.$nextTick(() => {
                this.syncExternalValue()
            })
        },

        currentValue() {
            return Number(this.getValue())
        },

        isDisabled() {
            return typeof this.disabled === 'function'
                ? this.disabled()
                : this.disabled
        },

        isSelected(option) {
            return Number(option) === this.currentValue()
        },

        select(value, shouldScroll = true) {
            if (this.isDisabled()) return

            const nextValue = Number(value)

            this.setValue(nextValue)
            this.lastSyncedValue = nextValue

            if (shouldScroll) {
                this.$nextTick(() => {
                    this.scrollToValue(nextValue)
                })
            }
        },

        syncExternalValue() {
            const value = this.currentValue()

            if (value === this.lastSyncedValue) return

            this.lastSyncedValue = value

            this.$nextTick(() => {
                this.scrollToValue(value, 'smooth')
            })
        },

        syncFromScroll() {
            if (this.isDisabled()) return

            const picker = this.$el

            if (!picker) return

            const pickerRect = picker.getBoundingClientRect()
            const pickerCenter = pickerRect.top + (pickerRect.height / 2)

            const options = [...picker.querySelectorAll('.picker-option')]

            if (!options.length) return

            const closest = options.reduce((selected, option) => {
                const optionRect = option.getBoundingClientRect()
                const optionCenter = optionRect.top + (optionRect.height / 2)

                const selectedRect = selected.getBoundingClientRect()
                const selectedCenter = selectedRect.top + (selectedRect.height / 2)

                return Math.abs(optionCenter - pickerCenter) < Math.abs(selectedCenter - pickerCenter)
                    ? option
                    : selected
            })

            this.select(closest.dataset.value, false)
        },

        scrollToValue(value, behavior = 'smooth') {
            const option = this.$el.querySelector(`[data-value="${value}"]`)

            if (!option) return

            option.scrollIntoView({
                block: 'center',
                behavior,
            })
        },
    }
}