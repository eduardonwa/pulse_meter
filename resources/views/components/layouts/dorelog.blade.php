@php($user = auth()->user())
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>DoreLog - Save your drills. Keep your tempo.</title>

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
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="product-events-endpoint" content="{{ route('analytics.events.store') }}">
    
    @vite(['resources/styles/main.scss', 'resources/js/app.js'])
    
    <style> [x-cloak] { display: none !important; } </style>
</head>

<body>
    <header class="site-header">
        <div class="site-header__inner">
            <a href="/" class="wordmark" wire:navigate>dorelog</a>

            <nav class="nav">
                @guest
                    <a href="{{ route('login') }}" wire:navigate>Log in</a>
                    <a href="{{ route('register') }}" wire:navigate>Register</a>
                @endguest
    
                @auth
                    <div class="actions">
                        <a class="user-name" href="{{ route('profile.edit') }}" wire:navigate>
                            @if ($user->plan === 'pro')
                                <x-heroicon-s-star class="profile-link__pro-icon"
                                    aria-hidden="true"
                                    width="16"
                                    height="16"
                                />
                            @endif
    
                            {{ $user->name }}
                        </a>

                        <div x-data="{ sidebar: false }">
                            <button class="button"
                                type="button"
                                data-type="icon"
                                :aria-expanded="sidebar"
                                aria-controls="sidebar"
                                @click="sidebar = !sidebar"
                            >
                                <x-heroicon-o-bars-3 />
                            </button>

                            <aside class="sidebar-menu"
                                id="sidebar"
                                x-show="sidebar"
                                x-cloak
                                @keydown.escape.window="sidebar = false"
                            >
                                <button class="button"
                                    type="button"
                                    aria-label="Close menu"
                                    data-type="icon"
                                    @click="sidebar = false"
                                >
                                    <x-heroicon-o-x-mark />
                                </button>

                                <nav>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button class="button" data-type="icon" type="submit">Log out</button>
                                    </form>
                                </nav>
                            </aside>
                        </div>
                    </div>
                @endauth
            </nav>
        </div>
    </header>

    {{ $slot }}
</body>
</html>