<div class="routine-drawer" :class="{ 'routine-drawer--open': drawerOpen }" x-cloak>
    <aside class="routine-drawer__panel"
        @click.outside="closeRoutine()"
        id="routine-details-drawer"
        role="dialog"
        aria-modal="true"
        aria-labelledby="routine-drawer-title"
        x-show="drawerOpen && selectedRoutine"
        x-trap.noscroll="drawerOpen"
        x-transition:enter="routine-drawer-enter"
        x-transition:enter-start="routine-drawer-enter-start"
        x-transition:enter-end="routine-drawer-enter-end"
        x-transition:leave="routine-drawer-leave"
        x-transition:leave-start="routine-drawer-leave-start"
        x-transition:leave-end="routine-drawer-leave-end"
    >
        <header class="routine-drawer__header">
            <div class="routine-drawer__heading">
                <span
                    class="eyebrow"
                    x-text="selectedRoutine?.typeLabel"
                ></span>

                <h2
                    class="title"
                    id="routine-drawer-title"
                    x-text="selectedRoutine?.title"
                ></h2>
            </div>

            <button
                class="routine-drawer__close button"
                data-type="icon"
                type="button"
                aria-label="Close routine details"
                x-ref="closeButton"
                @click="closeRoutine()"
            >
                <x-heroicon-o-x-circle />
            </button>
        </header>

        <div class="routine-drawer__body">
            <x-routines.facts
                variant="drawer"
                :dynamic="true"
            />

            <template x-if="selectedRoutine?.type === 'weekly_challenge'">
                <section class="routine-section routine-challenge">
                    <h3 class="routine-section__title">
                        Your challenge
                    </h3>

                    <p class="routine-section__description" x-text="challengeDescription()"></p>
                </section>
            </template>

            <template x-if="selectedRoutine?.purpose">
                <section class="routine-section routine-purpose">
                    <h3 class="routine-section__title">
                        Purpose
                    </h3>

                    <p
                        class="routine-section__description"
                        x-text="selectedRoutine.purpose"
                    ></p>
                </section>
            </template>

            <x-routines.exercise-list :dynamic="true" />

            <template x-if="selectedRoutine?.instructions">
                <section class="routine-section routine-instructions">
                    <h3 class="routine-section__title">
                        How to use it
                    </h3>

                    <p class="routine-section__description" x-text="selectedRoutine.instructions"></p>
                </section>
            </template>
        </div>

        <footer class="routine-drawer__footer">
            <a class="routine-drawer__cta button" data-type="primary" :href="selectedRoutine?.showUrl ?? '#'">
                Practice this routine
            </a>

            <small class="routine-drawer__notice">
                A free account is required.
            </small>
        </footer>
    </aside>
</div>