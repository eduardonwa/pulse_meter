<div
    class="modal-shell"
    x-show="isWaitingForNextExercise"
    x-trap.noscroll="isWaitingForNextExercise"
    x-transition
    @click.stop
>
    <div class="modal-panel"
        data-type="advance"
    >
        <h2 class="heading | desktop-only">PRESS SPACEBAR TO CONTINUE</h2>
    
        <button
            type="button"
            class="mobile-only | uppercase button"
            data-type="primary"
            @click="continueToNextExercise()"
        >
            Continue
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.689c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061A1.125 1.125 0 0 1 3 16.811V8.69ZM12.75 8.689c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061a1.125 1.125 0 0 1-1.683-.977V8.69Z" />
            </svg>
        </button>
    </div>
</div>