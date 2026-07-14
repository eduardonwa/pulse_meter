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

        getScroller() { return this.$refs.scroller ?? this.$el },

        getCurrentIndex() {
            const value = Number(this.getValue())

            return this.options.findIndex(option => {
                return Number(option) === value
            })
        },

        canGoPrevious() {
            return !this.disabled() && this.getCurrentIndex() > 0
        },

        canGoNext() {
            const index = this.getCurrentIndex()

            return (
                !this.disabled() &&
                index !== -1 &&
                index < this.options.length - 1
            )
        },

        previous() { this.moveBy(-1) },

        next() { this.moveBy(1) },

        moveBy(direction) {
            if (this.disabled()) {
                return
            }

            const currentIndex = this.getCurrentIndex()

            if (currentIndex === -1) {
                return
            }

            const nextIndex = currentIndex + direction

            if (nextIndex < 0 || nextIndex >= this.options.length) {
                return
            }

            const value = Number(this.options[nextIndex])

            this.scrollToIndex(nextIndex)
            this.setValue(value)
        },

        syncExternalValue(retries = 10) {
            const index = this.getCurrentIndex()

            if (index === -1) {
                return
            }

            const scroller = this.getScroller()

            if (scroller.clientHeight === 0 || scroller.scrollHeight === 0) {
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
            const scroller = this.getScroller()

            if (scroller.clientHeight === 0 || scroller.scrollHeight === 0) {
                return
            }

            this.isSyncingScroll = true

            requestAnimationFrame(() => {
                const option = scroller.querySelector('.picker-option')
                const optionHeight = option?.offsetHeight ?? 44

                scroller.scrollTop = index * optionHeight

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

            const scroller = this.getScroller()

            if (scroller.clientHeight === 0 || scroller.scrollHeight === 0) {
                return
            }

            const option = scroller.querySelector('.picker-option')
            const optionHeight = option?.offsetHeight ?? 44

            const index = Math.round(scroller.scrollTop / optionHeight)
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