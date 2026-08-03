export function notifications() {
    return {
        // TOAST
        toast: {
            visible: false,
            message: '',
            type: 'info',
            timer: null,
        },

        // CONFIRMATION MODAL
        confirmModal: {
            isOpen: false,
            title: '',
            message: '',
            confirmLabel: 'Confirm',
            action: null,
            isProcessing: false,
        },

        openConfirmModal({
            title,
            message,
            confirmLabel = 'Confirm',
            action,
        }) {
            this.confirmModal.title = title
            this.confirmModal.message = message
            this.confirmModal.confirmLabel =
                confirmLabel

            this.confirmModal.action = action
            this.confirmModal.isProcessing = false
            this.confirmModal.isOpen = true
        },

        closeConfirmModal(force = false) {
            if (
                this.confirmModal.isProcessing
                && !force
            ) {
                return
            }

            this.confirmModal.isOpen = false
            this.confirmModal.title = ''
            this.confirmModal.message = ''
            this.confirmModal.confirmLabel =
                'Confirm'

            this.confirmModal.action = null
            this.confirmModal.isProcessing = false
        },

        async confirmModalAction() {
            const action = this.confirmModal.action

            if (
                typeof action !== 'function'
                || this.confirmModal.isProcessing
            ) {
                return
            }

            this.confirmModal.isProcessing = true

            try {
                const succeeded = await action()

                if (succeeded !== false) {
                    this.closeConfirmModal(true)
                }
            } catch (error) {
                console.error(
                    'Confirmation action failed.',
                    error
                )
            } finally {
                this.confirmModal.isProcessing =
                    false
            }
        },

        // TOAST ACTIONS
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