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
    
    <style> [x-cloak] { display: none !important; } </style>
    
    @vite(['resources/styles/main.scss', 'resources/js/app.js'])

    @livewireStyles
</head>

<body>
    <header class="site-header" x-data="{ sidebar: false }">
        <div class="site-header__inner">
            <a href="/" class="wordmark" wire:navigate>dorelog</a>

            <nav class="nav">
                @auth
                    <button class="button"
                        data-type="icon-text"
                        wire:navigate
                        :aria-expanded="sidebar"
                        aria-controls="sidebar"
                        @click="sidebar = !sidebar"
                    >
                        @if ($user->plan === 'pro')
                            <x-heroicon-s-star class="profile-link__pro-icon"
                                aria-hidden="true"
                                width="16"
                                height="16"
                            />
                        @endif

                        {{ $user->name }}
                        <x-heroicon-m-chevron-down />
                    </button>
                @endauth

                @guest
                    <button class="button"
                        type="button"
                        data-type="icon"
                        :aria-expanded="sidebar"
                        aria-controls="sidebar"
                        @click="sidebar = !sidebar"
                    >
                        <x-heroicon-o-bars-3-center-left />
                    </button>
                @endguest
            </nav>
        </div>

        <aside class="sidebar"
            id="sidebar"
            x-show="sidebar"
            x-cloak
            x-trap.noscroll="sidebar"
            @keydown.escape.window="sidebar = false"
            @click.outside="sidebar = false"
        >
            <nav class="sidebar__menu">
                @guest
                    <a class="button" data-type="icon"
                        href="{{ route('login') }}"
                        wire:navigate
                    >
                        <x-heroicon-o-user />

                        <span>Log in</span>
                    </a>

                    <a class="button" data-type="icon"
                        href="{{ route('register') }}"
                        wire:navigate
                    >
                        <x-heroicon-o-cursor-arrow-rays />

                        <span>Register</span>
                    </a>
                @endguest

                @auth
                    <a class="button" data-type="icon" href="{{ route('profile.edit') }}">
                        <x-heroicon-o-user />
                        
                        <span>My profile</span>
                    </a>

                    <form class="logout" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="button" data-type="icon" type="submit">
                            <x-heroicon-o-arrow-right-start-on-rectangle />
                            
                            Log out
                        </button>
                    </form> 
                @endauth
            </nav>
        </aside>
    </header>

    {{ $slot }}
    
    @livewireScriptConfig
</body>
</html>