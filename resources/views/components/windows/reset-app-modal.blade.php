<div
    class="modal-shell"
    x-show="showResetAppModal"
    x-trap.noscroll="showResetAppModal"
    x-transition
    @click.self="showResetAppModal = false"
    x-cloak
>
    <div class="modal-panel"
        data-type="danger"
        @click.stop
    >
        <div class="heading">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>

            <div class="group">
                <h2 class="group__header">Reset app?</h2>
                <p class="group__hint">
                    This will delete your custom exercises and all recent sessions
                </p>
            </div>
        </div>

        <div class="modal-panel__actions">
            <button
                class="button"
                type="button"
                data-type="outline"
                @click="showResetAppModal = false"
            >
                Cancel
            </button>

            <button
                class="button"
                type="button"
                data-type="outline"
                data-variant="danger"
                @click="clearAllAppStorage()"
            >
                Reset
            </button>
        </div>
    </div>
</div>
