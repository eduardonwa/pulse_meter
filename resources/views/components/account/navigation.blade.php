<nav class="account-page__navigation" aria-label="Account">
    <a data-type="outline" data-variant="link" href="{{ route('profile.edit') }}" wire:navigate
        @class([
            'button',
            'is-selected' => request()->routeIs('profile.edit'),
        ])    
        @if (request()->routeIs('profile.edit'))
            aria-current="page"
        @endif
    >
        Profile
    </a>

    <a  data-type="outline" data-variant="link" href="{{ route('billing.index') }}" wire:navigate
        @class([
            'button',
            'is-selected' => request()->routeIs('billing.index'),
        ])
        @if (request()->routeIs('billing.index'))
            aria-current="page"
        @endif
    >
        Plans &amp; billing
    </a>
</nav>