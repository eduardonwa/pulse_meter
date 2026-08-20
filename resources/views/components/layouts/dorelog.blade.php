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
        <section class="home">
            <div class="home__about | container">
                <h2 class="home__header">A Music Practice App Built for Structured Routines</h2>
    
                <p class="home__text">
                    Dorelog brings your exercises, BPMs, metronome,
                    timer, and practice history together in one focused
                    music practice workflow.
                </p>
    
                <p class="home__text">
                    Build a routine, assign a tempo and duration to each
                    exercise, and move through your practice session without
                    constantly resetting your tools or checking your notes.
                </p>
            </div>

            <div class="home__features | container">
                <article>
                    <h3 class="home__header">Organize Your Practice Routine</h3>
                    <p class="home__text">
                        Save exercises with their tempo, duration,
                        and practice settings.
                    </p>
                </article>

                <article>
                    <h3 class="home__header">Practice With a Metronome and Timer</h3>
                    <p class="home__text">
                        Keep your practice tools and exercise list
                        inside the same workflow.
                    </p>
                </article>

                <article>
                    <h3 class="home__header">Keep Track of Your Progress</h3>
                    <p class="home__text">
                        Return to your exercises with your previous
                        BPM and session history available.
                    </p>
                </article>
            </div>

            <div class="home__contact-card | container">
                <h2 class="home__header">
                    Questions or feedback?
                </h2>

                <a class="home__contact-link" href="mailto:hello@dorelog.com">
                    Say hello <span aria-hidden="true">👋</span>
                </a>
            </div>

            <div class="home__contact-card | container">
                <h2 class="home__header">
                    Need help?
                </h2>

                <a class="home__contact-link" href="mailto:support@dorelog.com">
                    Contact support 📧
                </a>
            </div>
        </section>
    @endguest
    
    @livewireScriptConfig
</body>
</html>