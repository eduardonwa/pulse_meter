<nav class="mobile-bottom-nav" aria-label="Main navigation">
    <a href="{{ route('welcome') }}"
        class="mobile-bottom-nav__link {{ request()->routeIs('welcome') && request('panel') !== 'settings' ? 'is-active' : '' }}"
        @if (request()->routeIs('welcome') && request('panel') !== 'settings') aria-current="page" @endif>
        <x-heroicon-o-musical-note width="24" height="24" />
        <span>Practice</span>
    </a>
    <a href="{{ route('explore.index') }}"
        class="mobile-bottom-nav__link {{ request()->routeIs('explore.index') || request()->routeIs('routines.*') ? 'is-active' : '' }}"
        @if (request()->routeIs('explore.index') || request()->routeIs('routines.*')) aria-current="page" @endif>
        <x-heroicon-o-squares-2x2 width="24" height="24" />
        <span>Explore</span>
    </a>
    <a href="{{ route('welcome', ['panel' => 'settings']) }}"
        class="mobile-bottom-nav__link {{ request()->routeIs('welcome') && request('panel') === 'settings' ? 'is-active' : '' }}"
        @if (request()->routeIs('welcome') && request('panel') === 'settings') aria-current="page" @endif>
        <x-heroicon-o-cog-6-tooth width="24" height="24" />
        <span>Settings</span>
    </a>
</nav>
