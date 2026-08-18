@props([
    'routine',
    'template',
    'typeLabel',
    'totalSeconds' => 0,
    'totalMinutes' => 0,
])

<div class="routine-drawer" :class="{ 'routine-drawer--open': drawerOpen }" x-cloak>
    <aside class="routine-drawer__panel"
        id="routine-details-drawer"
        role="dialog"
        aria-modal="true"
        aria-labelledby="routine-drawer-title"
        x-show="drawerOpen"
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
                <span class="eyebrow">
                    {{ $typeLabel }}
                </span>

                <h2
                    class="title"
                    id="routine-drawer-title"
                >
                    {{ $routine->title }}
                </h2>
            </div>

            <button
                class="routine-drawer__close button"
                data-type="icon"
                type="button"
                aria-label="Close routine details"
                @click="drawerOpen = false"
            >
                <x-heroicon-o-x-mark />
            </button>
        </header>

        <div class="routine-drawer__body">
            <x-routines.facts
                :template="$template"
                :total-seconds="$totalSeconds"
                :total-minutes="$totalMinutes"
                variant="drawer"
            />

            @if ($template->type === 'weekly_challenge')
                <section
                    class="routine-section routine-challenge"
                >
                    <h3 class="routine-section__title">
                        Your challenge
                    </h3>

                    <p class="routine-section__description">
                        @if ($template->recommended_sessions)
                            Practice this routine
                            {{ $template->recommended_sessions }}
                            times
                        @else
                            Practice this routine
                        @endif

                        @if ($template->challenge_days)
                            over
                            {{ $template->challenge_days }}
                            days.
                        @else
                            this week.
                        @endif
                    </p>
                </section>
            @endif

            @if ($routine->purpose)
                <section
                    class="routine-section routine-purpose"
                >
                    <h3 class="routine-section__title">
                        Purpose
                    </h3>

                    <p class="routine-section__description">
                        {!! nl2br(e($routine->purpose)) !!}
                    </p>
                </section>
            @endif

            <x-routines.exercise-list
                :template="$template"
            />

            @if ($routine->instructions)
                <section
                    class="routine-section routine-instructions"
                >
                    <h3 class="routine-section__title">
                        How to use it
                    </h3>

                    <p class="routine-section__description">
                        {!! nl2br(e($routine->instructions)) !!}
                    </p>
                </section>
            @endif
        </div>

        <footer class="routine-drawer__footer">
            <button
                class="routine-drawer__cta button"
                data-type="primary"
                data-routine-template-id="{{ $template->id }}"
                type="button"
            >
                Practice this routine
            </button>

            <small class="routine-drawer__notice">
                A free account is required.
            </small>
        </footer>
    </aside>
</div>