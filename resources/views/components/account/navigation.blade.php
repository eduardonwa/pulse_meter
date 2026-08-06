<nav
    class="account-navigation"
    aria-label="Account"
>
    <a
        class="button"
        href="{{ route('profile.edit') }}"
        @if (request()->routeIs('profile.edit'))
            aria-current="page"
        @endif
        wire:navigate
    >
        Profile
    </a>

    <a
        class="button"
        href="{{ route('billing.index') }}"
        @if (request()->routeIs('billing.index'))
            aria-current="page"
        @endif
        wire:navigate
    >
        Plans &amp; billing
    </a>
</nav>