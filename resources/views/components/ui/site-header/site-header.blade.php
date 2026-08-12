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

    <x-ui.site-header.sidebar />
</header>