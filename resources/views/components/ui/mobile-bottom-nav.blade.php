<nav class="mobile-bottom-nav" aria-label="Main navigation">
    <a href="{{ route('welcome') }}" class="mobile-bottom-nav__link" aria-label="Exercises">
        <x-heroicon-o-check-circle width="24" height="24" />
        <span>Exercises</span>
    </a>
    <a href="{{ route('routines.index', ['locale' => app()->getLocale()]) }}"
        class="mobile-bottom-nav__link {{ request()->routeIs('routines.*') ? 'is-active' : '' }}"
        @if (request()->routeIs('routines.*')) aria-current="page" @endif
    >
        <x-heroicon-o-queue-list width="24" height="24" />
        <span>Routines</span>
    </a>
    <a href="{{ route('blog.index', ['locale' => app()->getLocale()]) }}"
        class="mobile-bottom-nav__link {{ request()->routeIs('blog.*') ? 'is-active' : '' }}"
        @if (request()->routeIs('blog.*')) aria-current="page" @endif
    >
        <x-heroicon-o-book-open width="24" height="24" />
        <span>Blog</span>
    </a>
</nav>
