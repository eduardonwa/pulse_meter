@php($user = auth()->user())
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Music Practice App & Routine Tracker | Dorelog</title>

    <meta name="title" content="DoreLog - The metronome that remembers your drills.">
    <meta name="description" content="Save your exercises, BPMs, and practice routines so you can pick up exactly where you left off.">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://dorelog.com/">
    <meta property="og:title" content="DoreLog - Save your drills. Keep your tempo.">
    <meta property="og:description" content="Save your exercises, BPMs, and practice routines so you can pick up exactly where you left off.">
    <meta property="og:image" content="https://dorelog.com/og-image.png">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://dorelog.com/">
    <meta name="twitter:title" content="DoreLog - Save your drills. Keep your tempo.">
    <meta name="twitter:description" content="Save your exercises, BPMs, and practice routines so you can pick up exactly where you left off.">
    <meta name="twitter:image" content="https://dorelog.com/og-image.png">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="product-events-endpoint" content="{{ route('analytics.events.store') }}">
    
    <style> [x-cloak] { display: none !important; } </style>
    
    @vite(['resources/styles/main.scss', 'resources/js/app.js'])

    @livewireStyles
</head>

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