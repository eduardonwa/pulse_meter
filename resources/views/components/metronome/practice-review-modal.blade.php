<div
    class="modal-shell"
    x-show="isPracticeReviewOpen"
    x-trap.noscroll="isPracticeReviewOpen"
    x-transition
    @click.stop
>
    <div class="modal-panel"
        data-type="practice-review"
    >
        <template x-if="!practiceFeeling">
            <div class="practice-review">
                <h2 class="heading">How are you feeling about your progress?</h2>

                <div class="practice-review__options">
                    <button
                        type="button"
                        class="button"
                        data-type="secondary"
                        @click="selectPracticeFeeling('estranged')"
                    >
                        <span class="emoji">😒</span>
                        Estranged
                    </button>

                    <button
                        type="button"
                        class="button"
                        data-type="secondary"
                        @click="selectPracticeFeeling('sad')"
                    >
                        <span class="emoji">😢</span>
                        Sad
                    </button>

                    <button
                        type="button"
                        class="button"
                        data-type="secondary"
                        @click="selectPracticeFeeling('happy')"
                    >
                        <span class="emoji">😃</span>
                        Happy
                    </button>

                    <button
                        type="button"
                        class="button"
                        data-type="secondary"
                        @click="selectPracticeFeeling('optimistic')"
                    >
                        <span class="emoji">🤩</span>
                        Very optimistic
                    </button>
                </div>
            </div>
        </template>

        <template x-if="practiceFeeling">
            <div class="review-confirmation">
                <h2 class="heading" x-text="practiceFeelingConfirmation"></h2>

                <button
                    type="button"
                    class="button"
                    data-type="outline"
                    @click="closePracticeReviewModal()"
                >
                    Close
                </button>
            </div>
        </template>
    </div>
</div>