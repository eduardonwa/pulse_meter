export function storage() {
    return {
        saveToLocalStorage() {
            localStorage.setItem(this.storageKey, JSON.stringify(this.steps))
        },

        loadFromLocalStorage() {
            const saved = localStorage.getItem(this.storageKey)

            if (saved) {
                this.steps = JSON.parse(saved)
            }
        },
    }
}