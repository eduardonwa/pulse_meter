export function numberPicker({
    options,
    getValue,
    setValue,
    disabled = () => false,
    format = value => value,
}) {
    return {
        options,
        getValue,
        setValue,
        disabled,
        format,

        isSyncingScroll: false,
        hasSyncedInitialValue: false,

        init() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.syncExternalValue()
                })
            })
        },

        syncExternalValue(retries = 10) {
            const value = Number(this.getValue())

            const index = this.options.findIndex(option => {
                return Number(option) === value
            })

            if (index === -1) {
                return
            }

            if (this.$el.clientHeight === 0 || this.$el.scrollHeight === 0) {
                if (retries <= 0) {
                    return
                }

                requestAnimationFrame(() => {
                    this.syncExternalValue(retries - 1)
                })

                return
            }

            this.scrollToIndex(index)

            setTimeout(() => {
                this.hasSyncedInitialValue = true
            }, 200)
        },

        scrollToIndex(index) {
            if (this.$el.clientHeight === 0 || this.$el.scrollHeight === 0) {
                return
            }

            this.isSyncingScroll = true

            requestAnimationFrame(() => {
                const option = this.$el.querySelector('.picker-option')
                const optionHeight = option?.offsetHeight ?? 44

                this.$el.scrollTop = index * optionHeight

                requestAnimationFrame(() => {
                    this.isSyncingScroll = false
                })
            })
        },

        syncFromScroll() {
            if (
                !this.hasSyncedInitialValue ||
                this.isSyncingScroll ||
                this.disabled()
            ) {
                return
            }

            if (this.$el.clientHeight === 0 || this.$el.scrollHeight === 0) {
                return
            }

            const option = this.$el.querySelector('.picker-option')
            const optionHeight = option?.offsetHeight ?? 44

            const index = Math.round(this.$el.scrollTop / optionHeight)
            const value = this.options[index]

            if (value === undefined) {
                return
            }

            if (Number(this.getValue()) === Number(value)) {
                return
            }

            this.setValue(Number(value))
        },

        select(option) {
            if (this.disabled()) {
                return
            }

            const index = this.options.findIndex(value => {
                return Number(value) === Number(option)
            })

            if (index === -1) {
                return
            }

            this.scrollToIndex(index)
            this.setValue(Number(option))
        },

        isSelected(option) {
            return Number(option) === Number(this.getValue())
        },
    }
}