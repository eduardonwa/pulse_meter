export function routineDialog() {
    return {
        isRoutineDialogOpen: false,

        openRoutineDialog() {
            this.isRoutineDialogOpen = true

            this.$nextTick(() => {
                const dialog = this.$refs.routineDialog

                if (dialog && !dialog.open) {
                    dialog.showModal()
                }
            })
        },

        closeRoutineDialog() {
            const dialog = this.$refs.routineDialog

            if (dialog?.open) {
                dialog.close()
            }

            this.isRoutineDialogOpen = false
        },
    }
}