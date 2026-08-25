@props([
    'title' => 'Music Practice App & Routine Tracker | Dorelog',
    'description' => 'Save your exercises, BPMs, and practice routines so you can pick up exactly where you left off.',
])

@php($user = auth()->user())

<!DOCTYPE html>
<html lang="en">
    
<x-head
    :title="$title"
    :description="$description"
/>

<body>
    <x-ui.site-header :user="$user" />

    {{ $slot }}
    
    @guest
        <section class="home" x-data="{ openSection: 'about' }">
            <div class="home__accordion accordion | container">
                <article class="accordion__item">
                    <h2 class="accordion__header">
                        <button class="button" data-type="icon-text" type="button"
                            :aria-expanded="(openSection === 'about').toString()"
                            aria-controls="home-about-panel"
                            @click="openSection = openSection === 'about' ? null : 'about'"
                        >
                            <span>About Dorelog</span>

                            <x-heroicon-o-chevron-down
                                class="accordion__icon"
                                x-bind:class="{ 'rotate-180': openSection === 'about' }"
                                aria-hidden="true"
                            />
                        </button>
                    </h2>

                    <div id="home-about-panel" class="accordion__panel"
                        x-show="openSection === 'about'" x-cloak
                    >
                        <h3 class="home__header">A Music Practice App Built for Structured Routines</h3>

                        <p class="home__text">
                            Dorelog brings your exercises, BPMs, metronome, timer, and practice
                            history together in one focused music practice workflow.
                        </p>

                        <p class="home__text">
                            Build a routine, assign a tempo and duration to each exercise,
                            and move through your practice session without constantly
                            resetting your tools or checking your notes.
                        </p>
                    </div>
                </article>

                <article class="accordion__item">
                    <h2 class="accordion__header">
                        <button class="button" data-type="icon-text" type="button"
                            :aria-expanded="(openSection === 'routines').toString()"
                            aria-controls="home-routines-panel"
                            @click="openSection = openSection === 'routines' ? null : 'routines'"
                        >
                            <span>How routines work</span>

                            <x-heroicon-o-chevron-down
                                class="accordion__icon"
                                x-bind:class="{ 'rotate-180': openSection === 'routines' }"
                                aria-hidden="true"
                            />
                        </button>
                    </h2>

                    <div id="home-routines-panel" class="accordion__panel"
                        x-show="openSection === 'routines'" x-cloak
                    >
                        <h3 class="home__header">Organize Your Practice Routine</h3>

                        <p class="home__text">
                            Save exercises with their tempo, duration, and practice settings.
                        </p>
                    </div>
                </article>

                <article class="accordion__item">
                    <h2 class="accordion__header">
                        <button class="button" data-type="icon-text" type="button"
                            :aria-expanded="(openSection === 'metronome').toString()"
                            aria-controls="home-metronome-panel"
                            @click="openSection = openSection === 'metronome' ? null : 'metronome'"
                        >
                            <span>Metronome and timer</span>

                            <x-heroicon-o-chevron-down
                                class="accordion__icon"
                                x-bind:class="{ 'rotate-180': openSection === 'metronome' }"
                                aria-hidden="true"
                            />
                        </button>
                    </h2>

                    <div id="home-metronome-panel" class="accordion__panel"
                        x-show="openSection === 'metronome'" x-cloak
                    >
                        <h3 class="home__header">Practice With a Metronome and Timer</h3>

                        <p class="home__text">
                            Keep your practice tools and exercise list inside the same workflow.
                        </p>
                    </div>
                </article>

                <article class="accordion__item">
                    <h2 class="accordion__header">
                        <button class="button" data-type="icon-text" type="button"
                            :aria-expanded="(openSection === 'history').toString()"
                            aria-controls="home-history-panel"
                            @click="openSection = openSection === 'history' ? null : 'history'"
                        >
                            <span>Practice history</span>

                            <x-heroicon-o-chevron-down
                                class="accordion__icon"
                                x-bind:class="{ 'rotate-180': openSection === 'history' }"
                                aria-hidden="true"
                            />
                        </button>
                    </h2>

                    <div id="home-history-panel" class="accordion__panel"
                        x-show="openSection === 'history'" x-cloak
                    >
                        <h3 class="home__header">Keep Track of Your Progress</h3>

                        <p class="home__text">
                            Return to your exercises with your previous BPM and session
                            history available.
                        </p>
                    </div>
                </article>
            </div>

            <div class="home__contact | container">
                <div class="home__contact-card">
                    <h2 class="home__header">Questions or feedback?</h2>

                    <a class="home__contact-link" href="mailto:hello@dorelog.com">
                        Say hello <span aria-hidden="true">👋</span>
                    </a>
                </div>

                <div class="home__contact-card">
                    <h2 class="home__header">Need help?</h2>

                    <a class="home__contact-link" href="mailto:support@dorelog.com">
                        Contact support <span aria-hidden="true">📧</span>
                    </a>
                </div>
            </div>
        </section>
    @endguest
        
    @livewireScriptConfig
</body>
</html>