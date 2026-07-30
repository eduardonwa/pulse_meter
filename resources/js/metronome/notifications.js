export function notifications() {
    return {
        showToast(
            message,
            type = 'info',
            duration = 3000
        ) {
            if (this.toast.timer) {
                clearTimeout(this.toast.timer)
            }

            this.toast.message = message
            this.toast.type = type
            this.toast.visible = true

            this.toast.timer = setTimeout(() => {
                this.hideToast()
            }, duration)
        },

        hideToast() {
            if (this.toast.timer) {
                clearTimeout(this.toast.timer)
                this.toast.timer = null
            }

            this.toast.visible = false
        },
    }
}