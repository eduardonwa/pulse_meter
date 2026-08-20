<header class="site-header" x-data="{ sidebar: false }">
    <div class="site-header__inner container" data-type="site-header">
        <a href="/" class="wordmark" wire:navigate>dorelog</a>
        
        <nav class="nav">
            @auth
                {{-- Trigger mobile --}}
                <div class="display-none--on-desktop">
                    <button class="button" data-type="icon-text" type="button"
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
                </div>
                
                {{-- Desktop trigger --}}
                <x-ui.site-header.horizontal-bar>
                    <x-slot:actions>
                        <button class="button" data-type="icon-text" type="button"
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
                    </x-slot:actions>
                </x-ui.site-header.horizontal-bar>
            @endauth

            @guest
                {{-- Trigger mobile --}}
                <button
                    class="button toggle-btn display-none--on-desktop"
                    data-type="icon"
                    type="button"
                    :aria-expanded="sidebar"
                    aria-controls="sidebar"
                    @click="sidebar = !sidebar"
                >
                    <x-heroicon-o-bars-3-center-left />
                </button>

                <x-ui.site-header.horizontal-bar>
                    <x-slot:actions>
                        <a href="{{ route('login') }}" wire:navigate>
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" wire:navigate>
                                Register
                            </a>
                        @endif
                    </x-slot:actions>
                </x-ui.site-header.horizontal-bar>
            @endguest
            
            <x-ui.site-header.sidebar />
        </nav>
    </div>
    
    @auth
        <x-ui.site-header.trial-mode :user="$user" />
    @endauth
</header>