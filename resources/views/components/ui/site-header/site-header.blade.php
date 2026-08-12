<header class="site-header" x-data="{ sidebar: false }">
    <div class="site-header__inner container" data-type="site-header">
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
                    @if ($user->hasLifetimePro())
                        <x-heroicon-s-bolt class="pro-icon pro-icon--lifetime" />
                    @elseif ($user->isPro())
                        <x-heroicon-s-star class="pro-icon pro-icon--monthly" />
                    @endif

                    {{ $user->name }}
                    <x-heroicon-m-chevron-down />
                </button>
            @endauth

            @guest
                <button class="button toggle-btn display-none--on-desktop"
                    type="button"
                    data-type="icon"
                    :aria-expanded="sidebar"
                    aria-controls="sidebar"
                    @click="sidebar = !sidebar"
                >
                    <x-heroicon-o-bars-3-center-left />
                </button>

                <div class="horizontal-nav display-none--until-desktop">
                    <div class="links">
                        <a class="button" data-type="icon" href="{{ route('blog.index', ['locale' => app()->getLocale()]) }}">
                            <x-heroicon-o-user-group />
                            Blog
                        </a>
                    </div>

                    <div class="auth">
                        <a class="button" data-type="icon" href="{{ route('login') }}" wire:navigate>
                            <x-heroicon-o-user />
    
                            <span>Log in</span>
                        </a>
                        
                        @if (Route::has('register'))
                            <a class="button" data-type="icon" href="{{ route('register') }}" wire:navigate>
                                <x-heroicon-o-cursor-arrow-rays />
    
                                <span>Register</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endguest
        </nav>
    </div>

    <x-ui.site-header.sidebar />
</header>