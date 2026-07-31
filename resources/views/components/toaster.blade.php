<div
    id="toaster"
    data-align="top"
    data-pos="right"
    aria-live="polite"
    aria-atomic="true"
    x-cloak
>
    <div
        class="toast"
        x-show="toast.visible"
        x-transition:enter="t-enter"
        x-transition:enter-start="t-enter-start--bottom"
        x-transition:enter-end="t-enter-end"
        x-transition:leave="t-leave"
        x-transition:leave-end="t-leave-end"
    >
        <div
            class="toast__body"
            :class="`toast__body--${toast.type}`"
        >
            <span x-text="toast.message"></span>

            <button
                class="button"
                data-type="icon"
                type="button"
                @click="hideToast()"
                aria-label="Close notification"
            >
                <x-heroicon-o-x-mark />
            </button>
        </div>
    </div>
</div>