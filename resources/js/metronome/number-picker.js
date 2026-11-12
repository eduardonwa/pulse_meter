export function numberPicker({
    options,
    getValue,
    setValue,
    disabled = () => false,
    format = value => value,
}) {
    return {
        // PICKER CONFIGURATION
        options,
        getValue,
        setValue,
        disabled,
        format,

        // PICKER STATE
        isSyncingScroll: false,
        hasSyncedInitialValue: false,
        previewValue: null,

        // Mount only the options surrounding the current scroll position.
        windowStart: 0,
        windowSize: 31,
        optionHeight: 44,

        get visibleOptions() {
            return this.options.slice(this.windowStart, this.windowStart + this.windowSize)
        },
        get topSpacerHeight() {
            return this.windowStart * this.optionHeight
        },
        get bottomSpacerHeight() {
            return Math.max(0, this.options.length - this.windowStart - this.windowSize) * this.optionHeight
        },
        updateWindowForIndex(index) {
            const maxStart = Math.max(0, this.options.length - this.windowSize)
            this.windowStart = Math.max(0, Math.min(maxStart, index - Math.floor(this.windowSize / 2)))
        },
        updateWindowFromScroll() {
            const scroller = this.getScroller()
            if (!scroller.clientHeight || this.isSyncingScroll) return
            this.updateWindowForIndex(Math.round(scroller.scrollTop / this.optionHeight))
        },
        measureOptionHeight() {
            const height = this.getScroller().querySelector('.picker-option')?.offsetHeight
            if (height > 0) this.optionHeight = height
        },

        // INITIALIZATION
        init() {
            this.updateWindowForIndex(Math.max(0, this.getCurrentIndex()))
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.syncExternalValue()
                })
            })

            // A picker inside x-show may be initialized while its height is 0.
            // Restore its position once it becomes visible without relying on
            // the parent component to dispatch picker:sync.
            if (typeof ResizeObserver !== 'undefined') {
                this.resizeObserver = new ResizeObserver(() => {
                    const scroller = this.getScroller()

                    if (scroller.clientHeight > 0 && !this.isSyncingScroll) {
                        this.syncExternalValue()
                    }
                })

                this.resizeObserver.observe(this.getScroller())
            }

            this.handlePageVisible = () => this.restoreVisiblePosition()
            this.handleVisibilityChange = () => {
                if (document.visibilityState === 'visible') {
                    this.restoreVisiblePosition()
                }
            }

            window.addEventListener('pageshow', this.handlePageVisible)
            document.addEventListener('visibilitychange', this.handleVisibilityChange)
        },

        destroy() {
            this.resizeObserver?.disconnect()
            window.removeEventListener('pageshow', this.handlePageVisible)
            document.removeEventListener('visibilitychange', this.handleVisibilityChange)
        },

        restoreVisiblePosition() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    if (this.getScroller().clientHeight > 0) {
                        this.syncExternalValue()
                    }
                })
            })
        },

        // PICKER NAVIGATION
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

        // SCROLL SYNCHRONIZATION
        getScroller() { return this.$refs.scroller ?? this.$el },

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

            this.measureOptionHeight()
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
            this.updateWindowForIndex(index)

            requestAnimationFrame(() => {
                this.measureOptionHeight()
                scroller.scrollTop = index * this.optionHeight

                requestAnimationFrame(() => {
                    this.isSyncingScroll = false
                })
            })
        },

        // Preview the centered option immediately, without persisting on every scroll frame.
        // Only enabled for the compact mobile exercise wheel.
        previewFromScroll() {
            if (
                !this.hasSyncedInitialValue ||
                this.isSyncingScroll ||
                this.disabled()
            ) {
                return
            }

            const scroller = this.getScroller()
            const index = Math.round(scroller.scrollTop / this.optionHeight)
            const value = this.options[index]

            if (value !== undefined && this.previewValue !== Number(value)) {
                this.previewValue = Number(value)
                this.$dispatch('picker:preview', { value: this.previewValue })
            }
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

            const index = Math.round(scroller.scrollTop / this.optionHeight)
            const value = this.options[index]

            if (value === undefined) {
                return
            }

            // Commit only after scrolling settles; the preview handles styling.
            this.previewValue = null

            if (Number(this.getValue()) === Number(value)) {
                return
            }

            this.setValue(Number(value))
        },

        // SELECTION
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
            return Number(option) === Number(this.previewValue ?? this.getValue())
        },
    }
}